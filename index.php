<?php
session_start();
require 'koneksi.php';

if (isset($_POST['login'])) {
    $username = dariPost($conn, 'username', 50);
    $password = dariPost($conn, 'password', 255);

    $cek = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username' AND password='$password'");

    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['admin_bengkel'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = true;
    }
}

$judul_halaman = 'Login';
$tanpa_navbar  = true;
$body_class    = 'gr-login';
include 'partials/header.php';
?>

<div class="container">
    <div class="row align-items-center gy-4 gx-lg-5">

        <div class="col-lg-7">
            <?php if ($logo_ada): ?>
                <img src="assets/img/logo.png" alt="" class="gr-logo mb-4" style="height:56px">
            <?php endif; ?>
            <h1 class="gr-hero-title">Bengkel<br>System</h1>
            <p class="gr-hero-text">Kelola pelanggan, kendaraan, servis, dan restorasi dari satu panel admin.</p>
        </div>

        <div class="col-lg-5">
            <div class="gr-login-card">
                <h2 class="gr-title mb-4">Masuk admin</h2>

                <?php if(isset($error)) : ?>
                    <div class="alert alert-danger py-2" role="alert">Username atau password salah. Periksa lagi lalu coba masuk.</div>
                <?php endif; ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required autocomplete="off" autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">Masuk sistem</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include 'partials/footer.php'; ?>
