<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

// Riwayat lengkap satu kendaraan: semua servis dan restorasi, dari yang terbaru
$id_mentah = potong($_GET['id'] ?? '', 50);
$id        = mysqli_real_escape_string($conn, $id_mentah);

$kendaraan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kendaraan.*, pelanggan.nama AS nama_pelanggan, pelanggan.telp AS telp_pelanggan
    FROM kendaraan JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
    WHERE kendaraan.id = '$id'"));

if (!$kendaraan) {
    flash('Kendaraan tidak ditemukan.', 'danger');
    pindah('kendaraan.php');
}

$riwayat = mysqli_query($conn, "SELECT * FROM (
        SELECT 'Servis' AS jenis, 'servis' AS berkas, id, keluhan AS uraian, status, status_bayar, biaya, tanggal_masuk, tanggal_selesai
            FROM servis WHERE id_kendaraan = '$id'
        UNION ALL
        SELECT 'Restorasi', 'restorasi', id, deskripsi, status, status_bayar, biaya, tanggal_masuk, tanggal_selesai
            FROM restorasi WHERE id_kendaraan = '$id'
    ) AS kerja
    ORDER BY tanggal_masuk DESC, LENGTH(id) DESC, id DESC");

$baris         = [];
$total         = 0;
$belum_dibayar = 0;
while ($r = mysqli_fetch_assoc($riwayat)) {
    $baris[] = $r;
    $total  += $r['biaya'];
    if ($r['status_bayar'] !== 'Lunas') { $belum_dibayar += $r['biaya']; }
}

$judul_halaman = 'Riwayat ' . $kendaraan['nopol'];
$menu_aktif    = 'kendaraan';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <a href="kendaraan.php" class="gr-back">&larr; Kembali ke data kendaraan</a>
        <h1 class="gr-title mt-1">Riwayat kendaraan</h1>
    </div>

    <?= tampilFlash(); ?>

    <div class="gr-tiles mb-4">
        <div class="gr-tile">
            <span>Kendaraan</span>
            <strong><?= h($kendaraan['merk']); ?></strong>
            <small><?= h($kendaraan['nopol']); ?> &middot; tahun <?= h($kendaraan['tahun']); ?></small>
        </div>
        <div class="gr-tile">
            <span>Pemilik</span>
            <strong><?= h($kendaraan['nama_pelanggan']); ?></strong>
            <small><?= h($kendaraan['telp_pelanggan']); ?></small>
        </div>
        <div class="gr-tile">
            <span>Total biaya pekerjaan</span>
            <strong><?= rupiah($total); ?></strong>
            <small><?= count($baris); ?> pekerjaan tercatat</small>
        </div>
        <div class="gr-tile <?= $belum_dibayar > 0 ? 'gr-tile-merah' : ''; ?>">
            <span>Belum dibayar</span>
            <strong><?= rupiah($belum_dibayar); ?></strong>
            <small><?= $belum_dibayar > 0 ? 'Masih ada tagihan' : 'Semua lunas'; ?></small>
        </div>
    </div>

    <div class="gr-panel">
        <div class="table-responsive">
            <table class="table table-gr table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Tanggal masuk</th>
                        <th>Jenis</th>
                        <th>ID</th>
                        <th>Pekerjaan</th>
                        <th>Biaya</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($baris): ?>
                        <?php foreach ($baris as $r): ?>
                            <tr>
                                <td class="kol-tgl">
                                    <?= tanggalIndo($r['tanggal_masuk']); ?>
                                    <?php if ($r['tanggal_selesai']): ?>
                                        <div class="kol-sub">Selesai <?= tanggalIndo($r['tanggal_selesai']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($r['jenis']); ?></td>
                                <td class="kol-id"><?= h($r['id']); ?></td>
                                <td class="kol-uraian"><?= h($r['uraian']); ?></td>
                                <td class="kol-rp"><?= rupiah($r['biaya']); ?></td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="st <?= kelasStatus($r['status']); ?>"><?= h($r['status']); ?></span>
                                        <span class="st <?= kelasBayar($r['status_bayar']); ?>"><?= h($r['status_bayar']); ?></span>
                                    </div>
                                </td>
                                <td class="kol-aksi">
                                    <?php if ($r['berkas'] === 'servis'): ?>
                                        <a href="servis_detail.php?id=<?= urlencode($r['id']); ?>" class="btn btn-secondary btn-sm">Detail</a>
                                    <?php endif; ?>
                                    <a href="nota.php?jenis=<?= h($r['berkas']); ?>&amp;id=<?= urlencode($r['id']); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Nota</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="gr-empty">Kendaraan ini belum pernah diservis atau direstorasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
