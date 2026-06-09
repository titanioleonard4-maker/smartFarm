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

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usia_bulan     = (int)   $_POST['usia_bulan'];
    $riwayat_vaksin =         $_POST['riwayat_vaksin'];
    $kode_kandang   =         $_POST['kode_kandang'];
    $jenis_kelamin  =         $_POST['jenis_kelamin'];
    $lingkar_dada   = (float) $_POST['lingkar_dada'];
    $panjang_badan  = (float) $_POST['panjang_badan'];
    $tinggi_gumba   = (float) $_POST['tinggi_gumba'];
    $id_peternak    = (int)   $_POST['id_peternak'];
    $tanggal_daftar = date('Y-m-d H:i:s');

    // Validasi 1: cek kandang sudah terisi atau belum
    $cek_kandang = $conn->prepare("SELECT COUNT(*) AS isi FROM hewan WHERE kode_kandang = :kode");
    $cek_kandang->execute([':kode' => $kode_kandang]);
    $isi_kandang = $cek_kandang->fetch()['isi'];

    if ($isi_kandang > 0) {
        $pesan = "<div class='alert alert-warning'><strong>Kandang $kode_kandang sudah terisi!</strong> Setiap kandang hanya boleh 1 hewan. Pilih kandang lain.</div>";
    } else {
        // Validasi 2: cek total hewan belum melebihi 56
        $cek_total   = $conn->query("SELECT COUNT(*) AS total FROM hewan");
        $total_hewan = $cek_total->fetch()['total'];

        if ($total_hewan >= 56) {
            $pesan = "<div class='alert alert-danger'><strong>Kapasitas penuh!</strong> Kandang sudah mencapai batas maksimal 56 hewan.</div>";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO hewan (id_peternak, usia_bulan, riwayat_vaksin, kode_kandang,
                 jenis_kelamin, lingkar_dada, panjang_badan, tinggi_gumba, tanggal_daftar)
                 VALUES (:id_peternak, :usia_bulan, :riwayat_vaksin, :kode_kandang,
                 :jenis_kelamin, :lingkar_dada, :panjang_badan, :tinggi_gumba, :tanggal_daftar)
                 RETURNING id_hewan"
            );
            $stmt->execute([
                ':id_peternak'    => $id_peternak,
                ':usia_bulan'     => $usia_bulan,
                ':riwayat_vaksin' => $riwayat_vaksin,
                ':kode_kandang'   => $kode_kandang,
                ':jenis_kelamin'  => $jenis_kelamin,
                ':lingkar_dada'   => $lingkar_dada,
                ':panjang_badan'  => $panjang_badan,
                ':tinggi_gumba'   => $tinggi_gumba,
                ':tanggal_daftar' => $tanggal_daftar,
            ]);

            $id_baru = $stmt->fetchColumn();

            $nomor_query = $conn->query("
                SELECT nomor FROM (
                    SELECT id_hewan,
                        ROW_NUMBER() OVER (
                            ORDER BY
                                LEFT(kode_kandang, 1),
                                CAST(SUBSTRING(kode_kandang FROM 2) AS INTEGER)
                        ) AS nomor
                    FROM hewan
                ) AS urutan
                WHERE id_hewan = $id_baru
            ");
            $nomor_urut = $nomor_query->fetchColumn();

            $pesan = "
                <div class='alert alert-success'>
                    Data hewan berhasil ditambahkan.<br>
                    <strong>ID Hewan: #$id_baru</strong> &nbsp;|&nbsp;
                    <strong>Nomor Urut: $nomor_urut</strong>
                </div>";
        }
    }
}

$peternak_list = $conn->query("
    SELECT id_peternak, nama_peternak
    FROM peternak
    WHERE role = 'peternak'
    ORDER BY id_peternak ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Hewan - Smart Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body data-bs-theme="dark" class="bg-dark text-light">

    <div class="container mt-4">
        <button class="btn btn-outline-success" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            ☰ Menu
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
                <?php if ($_SESSION['role'] === 'admin') : ?>
                    <li class="list-group-item bg-transparent"><a href="tambah_hewan.php" class="text-decoration-none text-success fw-bold">Tambah Hewan</a></li>
                    <li class="list-group-item bg-transparent"><a href="tambah_peternak.php" class="text-decoration-none text-light">Tambah Peternak</a></li>
                    <li class="list-group-item bg-transparent"><a href="tabel_peternak.php" class="text-decoration-none text-light">Data Peternak</a></li>
                <?php endif; ?>
                <li class="list-group-item bg-transparent"><a href="tabel_hewan.php" class="text-decoration-none text-light">Data Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="peta_kandang.php" class="text-decoration-none text-light">Peta Kandang</a></li>
                <li class="list-group-item bg-transparent mt-5"><a href="logout.php" class="text-decoration-none text-danger fw-bold">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container mt-4">
        <h2 class="mb-4 text-success fw-bold">Tambah Hewan Baru</h2>
        <?php
        $cek_cap   = $conn->query("SELECT COUNT(*) AS total FROM hewan");
        $total_cap = (int) $cek_cap->fetch()['total'];
        $sisa_cap  = 56 - $total_cap;
        $badge_cls = $sisa_cap === 0 ? 'danger' : ($sisa_cap <= 10 ? 'warning' : 'success');
        ?>
        <div class="alert alert-secondary d-flex justify-content-between align-items-center mb-3 py-2">
            <span>Kapasitas kandang</span>
            <span class="badge bg-<?= $badge_cls ?> fs-6"><?= $total_cap ?> / 56 terisi &nbsp;·&nbsp; <?= $sisa_cap ?> slot tersisa</span>
        </div>
        <?= $pesan; ?>
        <div class="card bg-dark border border-secondary p-4">
            <form action="tambah_hewan.php" method="POST">

                <div class="mb-3">
                    <label class="form-label">Pemilik</label>
                    <select name="id_peternak" class="form-select" required>
                        <option value="">Pilih Pemilik...</option>
                        <?php foreach ($peternak_list as $p) : ?>
                            <option value="<?= $p['id_peternak'] ?>">
                                #<?= $p['id_peternak'] ?> — <?= htmlspecialchars($p['nama_peternak']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kandang</label>
                    <select name="kode_kandang" id="kode_kandang" class="form-select" required onchange="tampilNomor(this)">
                        <option value="">Pilih Kandang...</option>
                        <?php
                        $terisi_query   = $conn->query("SELECT kode_kandang FROM hewan");
                        $kandang_terisi = $terisi_query->fetchAll(PDO::FETCH_COLUMN);

                        $no      = 1;
                        $kandang = $conn->query("
                            SELECT kode_kandang FROM kandang
                            ORDER BY
                                LEFT(kode_kandang, 1),
                                CAST(SUBSTRING(kode_kandang FROM 2) AS INTEGER)
                        ");
                        foreach ($kandang as $k) {
                            $kode  = htmlspecialchars($k['kode_kandang']);
                            $penuh = in_array($k['kode_kandang'], $kandang_terisi);
                            $label = $penuh ? "$kode (Terisi)" : $kode;
                            $dis   = $penuh ? " disabled" : "";
                            echo "<option value='$kode' data-nomor='$no'$dis>$label</option>";
                            $no++;
                        }
                        ?>
                    </select>
                    <div id="info-nomor" class="mt-2" style="display:none;">
                        <span class="badge bg-success fs-6">Nomor Hewan: <strong id="nomor-kandang"></strong></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select" required>
                        <option value="">Pilih Jenis Kelamin...</option>
                        <option value="Jantan">Jantan</option>
                        <option value="Betina">Betina</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usia (Bulan)</label>
                    <input type="number" name="usia_bulan" class="form-control" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Lingkar Dada / LiD (cm)
                        <small class="text-secondary fw-normal"> — diukur melingkar tepat di belakang siku kaki depan</small>
                    </label>
                    <input type="number" step="0.01" name="lingkar_dada" class="form-control" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Panjang Badan / PB (cm)
                        <small class="text-secondary fw-normal"> — dari sendi bahu depan hingga tulang panggul belakang</small>
                    </label>
                    <input type="number" step="0.01" name="panjang_badan" class="form-control" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tinggi Gumba (cm)
                        <small class="text-secondary fw-normal"> — dari permukaan tanah hingga titik tertinggi di atas pundak</small>
                    </label>
                    <input type="number" step="0.01" name="tinggi_gumba" class="form-control" min="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Riwayat Vaksin</label>
                    <textarea name="riwayat_vaksin" class="form-control" rows="3" placeholder="Kosongkan jika belum ada"></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100">Simpan Data</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function tampilNomor(select) {
        const opt   = select.options[select.selectedIndex];
        const nomor = opt.getAttribute('data-nomor');
        const info  = document.getElementById('info-nomor');
        if (nomor) {
            document.getElementById('nomor-kandang').textContent = nomor;
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
        }
    }
    </script>
</body>
</html>