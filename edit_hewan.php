<?php
session_start();
if (!isset($_SESSION['id_peternak']) || $_SESSION['role'] !== 'admin') {
    header("Location: tabel_hewan.php");
    exit;
}

include 'koneksi.php';

// A. Jika tombol simpan ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = (int) $_POST['id_hewan'];
    $usia   = (int) $_POST['usia_bulan'];
    $vaksin = $_POST['riwayat_vaksin'];
    $jenis  = $_POST['jenis_kelamin'];

    $stmt = $conn->prepare(
        "UPDATE hewan SET usia_bulan = :usia, riwayat_vaksin = :vaksin, jenis_kelamin = :jenis WHERE id_hewan = :id"
    );
    $stmt->execute([
        ':usia'   => $usia,
        ':vaksin' => $vaksin,
        ':jenis'  => $jenis,
        ':id'     => $id,
    ]);

    header("Location: tabel_hewan.php");
    exit;
}

// B. Cek parameter GET sebelum dipakai
if (!isset($_GET['id'])) {
    header("Location: tabel_hewan.php");
    exit;
}

$id   = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM hewan WHERE id_hewan = :id");
$stmt->execute([':id' => $id]);
$data = $stmt->fetch();

if (!$data) {
    header("Location: tabel_hewan.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Hewan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body data-bs-theme="dark" class="bg-dark text-light">
    <div class="container mt-5">
        <h2 class="text-success fw-bold">Edit Data Hewan</h2>
        <form method="POST" action="edit_hewan.php">
            <input type="hidden" name="id_hewan" value="<?= $data['id_hewan'] ?>">

            <div class="mb-3">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-select" required>
                    <option value="Jantan" <?= $data['jenis_kelamin'] === 'Jantan' ? 'selected' : '' ?>>Jantan</option>
                    <option value="Betina" <?= $data['jenis_kelamin'] === 'Betina' ? 'selected' : '' ?>>Betina</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Usia (Bulan)</label>
                <input type="number" name="usia_bulan" class="form-control" value="<?= $data['usia_bulan'] ?>" required>
            </div>
            <div class="mb-3">
                <label>Riwayat Vaksin</label>
                <textarea name="riwayat_vaksin" class="form-control"><?= htmlspecialchars($data['riwayat_vaksin'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
            <a href="tabel_hewan.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</body>
</html>