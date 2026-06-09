<?php
session_start();
require __DIR__ . '/koneksi.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM peternak WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $data = $stmt->fetch();

    if ($data && password_verify($password, $data['password'])) {
        $_SESSION['id_peternak']   = $data['id_peternak'];
        $_SESSION['nama_peternak'] = $data['nama_peternak'];
        $_SESSION['role']          = $data['role'];
        header("Location: dashboard.php");
        exit;
    }
    $pesan = "<div class='alert alert-danger'>Username atau Password salah!</div>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body data-bs-theme="dark" class="bg-dark text-light d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card bg-dark border border-success shadow-lg">
                    <div class="card-body p-5">
                        <h2 class="text-center text-success mb-4 fw-bold">SMART FARM LOGIN</h2>

                        <?= $pesan; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-light">Username</label>
                                <input type="text" name="username" class="form-control bg-secondary text-light border-0" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-light">Password</label>
                                <input type="password" name="password" class="form-control bg-secondary text-light border-0" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold">Masuk</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>