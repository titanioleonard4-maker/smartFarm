<?php
// 1. Memulai session agar kita bisa mengakses data session yang sedang aktif
session_start();

// 2. Menghapus semua data yang tersimpan di dalam session
session_unset();

// 3. Menghancurkan (menghapus) session itu sendiri dari server
session_destroy();

// 4. Mengalihkan (redirect) pengguna kembali ke halaman login
header("Location: login.php");
exit;
?>