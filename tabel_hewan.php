<?php
session_start();
if (!isset($_SESSION['id_peternak'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

$is_admin = ($_SESSION['role'] === 'admin');

if ($is_admin) {
    $query = "
        SELECT h.*, p.nama_peternak,
            ROW_NUMBER() OVER (
                ORDER BY LEFT(h.kode_kandang, 1), CAST(SUBSTRING(h.kode_kandang FROM 2) AS INTEGER)
            ) AS nomor_urut
        FROM hewan h
        LEFT JOIN peternak p ON h.id_peternak = p.id_peternak
        ORDER BY LEFT(h.kode_kandang, 1), CAST(SUBSTRING(h.kode_kandang FROM 2) AS INTEGER)
    ";
} else {
    $id_user = (int) $_SESSION['id_peternak'];
    $query = "
        SELECT *,
            ROW_NUMBER() OVER (
                ORDER BY LEFT(kode_kandang, 1), CAST(SUBSTRING(kode_kandang FROM 2) AS INTEGER)
            ) AS nomor_urut
        FROM hewan
        WHERE id_peternak = $id_user
        ORDER BY LEFT(kode_kandang, 1), CAST(SUBSTRING(kode_kandang FROM 2) AS INTEGER)
    ";
}

$result = $conn->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Hewan - Smart Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body data-bs-theme="dark" class="bg-dark text-light">

    <div class="container mt-4">
        <button class="btn btn-outline-success" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            Menu
        </button>
    </div>

    <div class="offcanvas offcanvas-start bg-dark border-end border-success" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title text-success fw-bold">Smart Farm</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent"><a href="dashboard.php" class="text-decoration-none text-light">Dashboard Utama</a></li>
                <?php if ($is_admin) : ?>
                    <li class="list-group-item bg-transparent"><a href="tambah_hewan.php" class="text-decoration-none text-light">Tambah Hewan</a></li>
                    <li class="list-group-item bg-transparent"><a href="tambah_peternak.php" class="text-decoration-none text-light">Tambah Peternak</a></li>
                    <li class="list-group-item bg-transparent"><a href="tabel_peternak.php" class="text-decoration-none text-light">Data Peternak</a></li>
                <?php endif; ?>
                <li class="list-group-item bg-transparent"><a href="tabel_hewan.php" class="text-decoration-none text-success fw-bold">Data Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="peta_kandang.php" class="text-decoration-none text-light">Peta Kandang</a></li>
                <li class="list-group-item bg-transparent mt-5"><a href="logout.php" class="text-decoration-none text-danger fw-bold">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container mt-4">
        <h2 class="mb-4 text-success fw-bold">Data Hewan</h2>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>ID Hewan</th>
                    <th>Kandang</th>
                    <?php if ($is_admin) { echo "<th>ID Pemilik</th><th>Nama Pemilik</th>"; } ?>
                    <th>Jenis Kelamin</th>
                    <th>Usia (Bulan)</th>
                    <th>Lingkar Dada (cm)</th>
                    <th>Panjang Badan (cm)</th>
                    <th>Tinggi Gumba (cm)</th>
                    <th>Riwayat Vaksin</th>
                    <th>Tanggal Daftar</th>
                    <?php if ($is_admin) { echo "<th>Aksi</th>"; } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row) { ?>
                <tr>
                    <td><?= $row['nomor_urut'] ?></td>
                    <td><?= htmlspecialchars($row['kode_kandang'] ?? '-') ?></td>
                    <?php if ($is_admin) { ?>
                        <td><?= $row['id_peternak'] ?></td>
                        <td><?= htmlspecialchars($row['nama_peternak'] ?? '-') ?></td>
                    <?php } ?>
                    <td><?= htmlspecialchars($row['jenis_kelamin'] ?? '-') ?></td>
                    <td><?= $row['usia_bulan'] ?? '-' ?></td>
                    <td><?= $row['lingkar_dada'] ?? '-' ?></td>
                    <td><?= $row['panjang_badan'] ?? '-' ?></td>
                    <td><?= $row['tinggi_gumba'] ?? '-' ?></td>
                    <td><?= htmlspecialchars($row['riwayat_vaksin'] ?? '-') ?></td>
                    <td><?= date('d-m-Y', strtotime($row['tanggal_daftar'])) ?></td>
                    <?php if ($is_admin) { ?>
                    <td>
                        <a href='edit_hewan.php?id=<?= $row['id_hewan'] ?>' class='btn btn-sm btn-warning'>Edit</a>
                        <a href='hapus_hewan.php?id=<?= $row['id_hewan'] ?>' class='btn btn-sm btn-danger'
                        onclick="return confirm('Yakin hapus hewan #<?= $row['nomor_urut'] ?>?')">Hapus</a>
                    </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>