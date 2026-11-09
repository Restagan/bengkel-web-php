<?php
session_start();
require 'koneksi.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $cek = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");
    
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['admin_bengkel'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Manajemen Bengkel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-secondary bg-opacity-10 d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Login Admin Bengkel</h4>
                        
                        <?php if(isset($error)) : ?>
                            <div class="alert alert-danger py-2">Username atau password salah!</div>
                        <?php endif; ?>

                        <form action="" method="post">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Username</label>
                                <input type="text" name="username" class="form-control" required autocomplete="off">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 fw-medium">Masuk Sistem</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>