<?php
session_start();
if (!isset($_SESSION['id_peternak'])) {
    header("Location: login.php");
    exit;
}
header("Location: dashboard.php");
?>

<!DOCTYPE html>
<html lang="id">

</html>