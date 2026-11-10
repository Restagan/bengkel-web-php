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

$persen_servis    = $total_pendapatan > 0 ? round($pendapatan_servis / $total_pendapatan * 100) : 0;
$persen_restorasi = $total_pendapatan > 0 ? 100 - $persen_servis : 0;

$judul_halaman = 'Dashboard';
$menu_aktif    = 'dashboard';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="gr-title">Dashboard utama</h1>
        <p class="gr-sub">Ringkasan seluruh data bengkel.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="gr-tile">
                <div class="gr-tile-label">Total pelanggan</div>
                <div class="gr-tile-value"><?= $total_pelanggan; ?><small>orang</small></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="gr-tile">
                <div class="gr-tile-label">Total kendaraan</div>
                <div class="gr-tile-value"><?= $total_kendaraan; ?><small>unit</small></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gr-tile gr-tile-hot">
                <div class="gr-tile-label">Total pendapatan</div>
                <div class="gr-tile-value"><small>Rp</small><?= number_format($total_pendapatan, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <div class="gr-panel p-4 mb-4">
        <h2 class="gr-h2">Rincian pendapatan</h2>

        <div class="gr-sector mb-3" role="img" aria-label="Servis <?= $persen_servis; ?> persen, restorasi <?= $persen_restorasi; ?> persen">
            <span class="seg seg-a" style="width: <?= $persen_servis; ?>%"></span>
            <span class="seg seg-b" style="width: <?= $persen_restorasi; ?>%"></span>
        </div>

        <div class="row g-3">
            <div class="col-sm-6">
                <div class="gr-legend">
                    <i class="sw sw-a"></i>
                    <div>
                        <div class="gr-legend-name">Servis</div>
                        <div class="gr-legend-val">Rp <?= number_format($pendapatan_servis, 0, ',', '.'); ?><small><?= $persen_servis; ?>%</small></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="gr-legend">
                    <i class="sw sw-b"></i>
                    <div>
                        <div class="gr-legend-name">Restorasi</div>
                        <div class="gr-legend-val">Rp <?= number_format($pendapatan_restorasi, 0, ',', '.'); ?><small><?= $persen_restorasi; ?>%</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="gr-h2">Akses cepat</h2>
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="pelanggan.php">
                <strong>Pelanggan</strong>
                <span>Tambah, cari, dan hapus pemilik</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="kendaraan.php">
                <strong>Kendaraan</strong>
                <span>Kendaraan beserta pemiliknya</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="servis.php">
                <strong>Servis</strong>
                <span>Keluhan, biaya, dan status</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="restorasi.php">
                <strong>Restorasi</strong>
                <span>Pekerjaan dan estimasi biaya</span>
            </a>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
