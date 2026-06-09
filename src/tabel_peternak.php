<?php
session_start();
if (!isset($_SESSION['id_peternak'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Akses Ditolak!'); window.location.href='tabel_hewan.php';</script>";
    exit;
}

require __DIR__ . '/koneksi.php';

// Handle hapus peternak
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    try {
        $conn->exec("DELETE FROM peternak WHERE id_peternak = $id");
        echo "<script>alert('Peternak berhasil dihapus.'); window.location.href='tabel_peternak.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Gagal menghapus peternak.'); window.location.href='tabel_peternak.php';</script>";
    }
    exit;
}

$query = "SELECT p.id_peternak, p.nama_peternak, p.username, p.no_hp, p.alamat, p.role,
                COUNT(h.id_hewan) AS total_hewan
        FROM peternak p
        LEFT JOIN hewan h ON p.id_peternak = h.id_peternak
        GROUP BY p.id_peternak
        ORDER BY p.id_peternak ASC";
$result = $conn->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Peternak - Smart Farm</title>
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
                <li class="list-group-item bg-transparent"><a href="tambah_hewan.php" class="text-decoration-none text-light">Tambah Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="tambah_peternak.php" class="text-decoration-none text-light">Tambah Peternak</a></li>
                <li class="list-group-item bg-transparent"><a href="tabel_peternak.php" class="text-decoration-none text-success fw-bold">Data Peternak</a></li>
                <li class="list-group-item bg-transparent"><a href="tabel_hewan.php" class="text-decoration-none text-light">Data Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="peta_kandang.php" class="text-decoration-none text-light">Peta Kandang</a></li>
                <li class="list-group-item bg-transparent mt-5"><a href="logout.php" class="text-decoration-none text-danger fw-bold">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container mt-4">
        <h2 class="mb-4 text-success fw-bold">Data Peternak</h2>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID Peternak</th>
                    <th>Nama Peternak</th>
                    <th>Username</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Role</th>
                    <th>Jumlah Hewan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($result as $row) {
                    $no_hp  = $row['no_hp']  ? htmlspecialchars($row['no_hp'])  : '-';
                    $alamat = $row['alamat'] ? htmlspecialchars($row['alamat']) : '-';
                    $id     = (int) $row['id_peternak'];
                    $nama   = htmlspecialchars($row['nama_peternak']);
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= $id ?></td>
                    <td><?= $nama ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $no_hp ?></td>
                    <td><?= $alamat ?></td>
                    <td><?= htmlspecialchars($row['role']) ?></td>
                    <td><?= (int) $row['total_hewan'] ?> ekor</td>
                    <td>
                        <a href="tabel_peternak.php?hapus=<?= $id ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Hapus peternak <?= $nama ?>? Semua data hewan terkait juga akan terhapus.')">
                            Hapus
                        </a>
                    </td>
                </tr>
                <?php $no++; } ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>