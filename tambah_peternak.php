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

include 'koneksi.php';

$pesan       = "";
$pesan_reset = "";

// ── HANDLER RESET PASSWORD ────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aksi']) && $_POST['aksi'] === 'reset_password') {
    $id_reset   = (int) $_POST['id_reset'];
    $pw_baru    = $_POST['password_baru'];
    $pw_konfirm = $_POST['password_konfirm'];

    if (strlen($pw_baru) < 6) {
        $pesan_reset = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>Password terlalu pendek!</strong> Minimal 6 karakter.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } elseif ($pw_baru !== $pw_konfirm) {
        $pesan_reset = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>Konfirmasi password tidak cocok!</strong>
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } else {
        $hash       = password_hash($pw_baru, PASSWORD_DEFAULT);
        $stmt_reset = $conn->prepare("UPDATE peternak SET password = :pw WHERE id_peternak = :id");
        $stmt_reset->execute([':pw' => $hash, ':id' => $id_reset]);

        $r = $conn->query("SELECT nama_peternak FROM peternak WHERE id_peternak = $id_reset")->fetch();
        $pesan_reset = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            Password <strong>" . htmlspecialchars($r['nama_peternak']) . "</strong> berhasil diubah.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// ── HANDLER TAMBAH PETERNAK ───────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($_POST['aksi']) || $_POST['aksi'] !== 'reset_password')) {
    $nama_peternak = $_POST['nama_peternak'];
    $username      = $_POST['username'];
    $raw_password  = $_POST['password'];
    $no_hp         = $_POST['no_hp'];
    $alamat        = $_POST['alamat'];
    $role          = 'peternak';

    if (strlen($raw_password) < 6) {
        $pesan = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
            <strong>Password terlalu pendek!</strong> Minimal 6 karakter.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);

        $stmt_cek = $conn->prepare("SELECT id_peternak FROM peternak WHERE username = :username");
        $stmt_cek->execute([':username' => $username]);

        if ($stmt_cek->rowCount() > 0) {
            $pesan = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                <strong>Username sudah digunakan!</strong> Pilih username lain untuk <b>" . htmlspecialchars($username) . "</b>.
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO peternak (nama_peternak, username, password, no_hp, alamat, role)
                 VALUES (:nama, :username, :password, :no_hp, :alamat, :role)
                 RETURNING id_peternak"
            );
            $stmt->execute([
                ':nama'     => $nama_peternak,
                ':username' => $username,
                ':password' => $password,
                ':no_hp'    => $no_hp,
                ':alamat'   => $alamat,
                ':role'     => $role,
            ]);
            $id_baru = $stmt->fetchColumn();

            $pesan = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                <h5 class='alert-heading'>Peternak Berhasil Ditambahkan!</h5>
                <hr>
                <div class='row'>
                    <div class='col-sm-4'><small class='text-muted'>ID Peternak</small><br><strong>#$id_baru</strong></div>
                    <div class='col-sm-4'><small class='text-muted'>Nama</small><br><strong>" . htmlspecialchars($nama_peternak) . "</strong></div>
                    <div class='col-sm-4'><small class='text-muted'>Username</small><br><strong>" . htmlspecialchars($username) . "</strong></div>
                </div>
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
        }
    }
}

$list_reset = $conn->query("SELECT id_peternak, nama_peternak, username FROM peternak ORDER BY id_peternak ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Peternak - Smart Farm</title>
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
                <li class="list-group-item bg-transparent"><a href="tambah_peternak.php" class="text-decoration-none text-success fw-bold">Tambah Peternak</a></li>
                <li class="list-group-item bg-transparent"><a href="tabel_peternak.php" class="text-decoration-none text-light">Data Peternak</a></li>
                <li class="list-group-item bg-transparent"><a href="tabel_hewan.php" class="text-decoration-none text-light">Data Hewan</a></li>
                <li class="list-group-item bg-transparent"><a href="peta_kandang.php" class="text-decoration-none text-light">Peta Kandang</a></li>
                <li class="list-group-item bg-transparent mt-5"><a href="logout.php" class="text-decoration-none text-danger fw-bold">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container mt-4">
        <h2 class="mb-4 text-success fw-bold">Tambah Peternak Baru</h2>
        <?= $pesan; ?>
        <div class="card bg-dark border border-secondary p-4">
            <form action="tambah_peternak.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_peternak" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                    <small class="text-secondary">Digunakan untuk login, tidak bisa sama dengan peternak lain.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small class="text-secondary">Minimal 6 karakter.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 08123456789">
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="3" placeholder="Kosongkan jika tidak perlu"></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100">Simpan Peternak</button>
            </form>
        </div>
    </div>

    <div class="container mt-5 mb-5">
        <h4 class="text-warning fw-bold mb-3">Ubah Password Peternak</h4>
        <?= $pesan_reset; ?>
        <div class="card bg-dark border border-secondary p-4">
            <form action="tambah_peternak.php" method="POST">
                <input type="hidden" name="aksi" value="reset_password">
                <div class="mb-3">
                    <label class="form-label">Pilih Peternak</label>
                    <select name="id_reset" class="form-select" required>
                        <option value="">Pilih peternak...</option>
                        <?php foreach ($list_reset as $pr) : ?>
                            <option value="<?= $pr['id_peternak'] ?>">
                                #<?= $pr['id_peternak'] ?> — <?= htmlspecialchars($pr['nama_peternak']) ?> (<?= htmlspecialchars($pr['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" required minlength="6">
                    <small class="text-secondary">Minimal 6 karakter.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_konfirm" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold">Ubah Password</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>