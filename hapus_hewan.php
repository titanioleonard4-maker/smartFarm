<?php
session_start();
if (!isset($_SESSION['id_peternak']) || $_SESSION['role'] !== 'admin') {
    header("Location: tabel_hewan.php");
    exit;
}

include 'koneksi.php';

if (isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM hewan WHERE id_hewan = :id");

    if ($stmt->execute([':id' => $id])) {
        echo "<script>alert('Data berhasil dihapus!'); window.location.href='tabel_hewan.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data.'); window.location.href='tabel_hewan.php';</script>";
    }
}
?>