<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    header("Location: index.php");
    exit;
}

$total_pelanggan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pelanggan"))['total'];
$total_kendaraan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kendaraan"))['total'];

$pendapatan_servis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(biaya) as total FROM servis"))['total'] ?? 0;
$pendapatan_restorasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(biaya) as total FROM restorasi"))['total'] ?? 0;
$total_pendapatan = $pendapatan_servis +$pendapatan_restorasi;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sistem Manajemen Bengkel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">BENGKEL SYSTEM</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="pelanggan.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="kendaraan.php">Data Kendaraan</a></li>
                    <li class="nav-item"><a class="nav-link" href="servis.php">Data Servis</a></li>
                    <li class="nav-item"><a class="nav-link" href="restorasi.php">Data Restorasi</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="mb-4">Dashboard Utama</h3>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white p-3 shadow-sm">
                    <h5>Total Pelanggan</h5>
                    <h2><?= $total_pelanggan; ?> Orang</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white p-3 shadow-sm">
                    <h5>Total Kendaraan</h5>
                    <h2><?= $total_kendaraan; ?> Unit</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark p-3 shadow-sm">
                    <h5>Total Pendapatan</h5>
                    <h2>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>
    </div>

</body>
</html>