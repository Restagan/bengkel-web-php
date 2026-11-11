<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

function satuAngka($conn, $sql) {
    $baris = mysqli_fetch_row(mysqli_query($conn, $sql));
    return $baris[0] ?? 0;
}

$total_pelanggan = satuAngka($conn, "SELECT COUNT(*) FROM pelanggan");
$total_kendaraan = satuAngka($conn, "SELECT COUNT(*) FROM kendaraan");

$pendapatan_servis    = satuAngka($conn, "SELECT COALESCE(SUM(biaya), 0) FROM servis");
$pendapatan_restorasi = satuAngka($conn, "SELECT COALESCE(SUM(biaya), 0) FROM restorasi");
$total_pendapatan     = $pendapatan_servis + $pendapatan_restorasi;

$persen_servis    = $total_pendapatan > 0 ? round($pendapatan_servis / $total_pendapatan * 100) : 0;
$persen_restorasi = $total_pendapatan > 0 ? 100 - $persen_servis : 0;

$belum_dibayar = satuAngka($conn, "SELECT COALESCE(SUM(biaya), 0) FROM servis WHERE status_bayar <> 'Lunas'")
               + satuAngka($conn, "SELECT COALESCE(SUM(biaya), 0) FROM restorasi WHERE status_bayar <> 'Lunas'");
$jml_berjalan  = satuAngka($conn, "SELECT COUNT(*) FROM servis WHERE status <> 'Selesai'")
               + satuAngka($conn, "SELECT COUNT(*) FROM restorasi WHERE status <> 'Selesai'");
$jml_menipis   = satuAngka($conn, "SELECT COUNT(*) FROM sparepart WHERE stok <= stok_minimum");

$bulan_ini = date('Y-m');
$enam      = [];
for ($i = 5; $i >= 0; $i--) {
    $kunci        = date('Y-m', strtotime("first day of -$i month"));
    $enam[$kunci] = 0;
}
$awal_grafik = array_key_first($enam) . '-01';
$hasil = mysqli_query($conn, "SELECT SUBSTR(tanggal_masuk, 1, 7) AS bulan, SUM(biaya) AS total FROM (
        SELECT tanggal_masuk, biaya FROM servis
        UNION ALL
        SELECT tanggal_masuk, biaya FROM restorasi
    ) AS kerja
    WHERE tanggal_masuk >= '$awal_grafik'
    GROUP BY SUBSTR(tanggal_masuk, 1, 7)");
while ($r = mysqli_fetch_assoc($hasil)) {
    if (isset($enam[$r['bulan']])) { $enam[$r['bulan']] = (float)$r['total']; }
}
$maks_bulan = max($enam);
$maks_px    = 150;

$berjalan = mysqli_query($conn, "SELECT kerja.*, kendaraan.merk, kendaraan.nopol, mekanik.nama AS nama_mekanik
    FROM (
        SELECT 'Servis' AS jenis, id, id_kendaraan, id_mekanik, keluhan AS uraian, status, tanggal_masuk FROM servis WHERE status <> 'Selesai'
        UNION ALL
        SELECT 'Restorasi', id, id_kendaraan, id_mekanik, deskripsi, status, tanggal_masuk FROM restorasi WHERE status <> 'Selesai'
    ) AS kerja
    JOIN kendaraan ON kerja.id_kendaraan = kendaraan.id
    LEFT JOIN mekanik ON kerja.id_mekanik = mekanik.id
    ORDER BY kerja.tanggal_masuk ASC, LENGTH(kerja.id) ASC, kerja.id ASC
    LIMIT 8");

$menipis = mysqli_query($conn, "SELECT id, nama, satuan, stok, stok_minimum FROM sparepart
    WHERE stok <= stok_minimum ORDER BY stok ASC, nama ASC LIMIT 6");

$tunggakan_url = 'laporan.php?' . http_build_query(['dari' => '2000-01-01', 'sampai' => hariIni(), 'bayar' => 'Belum Lunas']);

$judul_halaman = 'Dashboard';
$menu_aktif    = 'dashboard';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="gr-title">Dashboard utama</h1>
        <p class="gr-sub">Ringkasan seluruh data bengkel.</p>
    </div>

    <?= tampilFlash(); ?>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="gr-tile">
                <div class="gr-tile-label">Total pelanggan</div>
                <div class="gr-tile-value"><?= (int)$total_pelanggan; ?><small>orang</small></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="gr-tile">
                <div class="gr-tile-label">Total kendaraan</div>
                <div class="gr-tile-value"><?= (int)$total_kendaraan; ?><small>unit</small></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gr-tile gr-tile-hot">
                <div class="gr-tile-label">Total pendapatan</div>
                <div class="gr-tile-value"><small>Rp</small><?= number_format($total_pendapatan, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <div class="gr-tiles mb-4">
        <a class="gr-tile gr-tile-hijau" href="laporan.php">
            <span>Pendapatan bulan ini</span>
            <strong><?= rupiah($enam[$bulan_ini]); ?></strong>
            <small>Lihat laporan lengkap</small>
        </a>
        <a class="gr-tile gr-tile-kuning" href="servis.php">
            <span>Pekerjaan berjalan</span>
            <strong><?= (int)$jml_berjalan; ?> pekerjaan</strong>
            <small>Servis dan restorasi yang belum selesai</small>
        </a>
        <a class="gr-tile <?= $belum_dibayar > 0 ? 'gr-tile-merah' : ''; ?>" href="<?= h($tunggakan_url); ?>">
            <span>Belum dibayar</span>
            <strong><?= rupiah($belum_dibayar); ?></strong>
            <small><?= $belum_dibayar > 0 ? 'Klik untuk melihat daftar tagihan' : 'Tidak ada tunggakan'; ?></small>
        </a>
        <a class="gr-tile <?= $jml_menipis > 0 ? 'gr-tile-merah' : ''; ?>" href="sparepart.php?kondisi=menipis">
            <span>Stok menipis</span>
            <strong><?= (int)$jml_menipis; ?> item</strong>
            <small><?= $jml_menipis > 0 ? 'Perlu dibeli ulang' : 'Semua stok aman'; ?></small>
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="gr-panel gr-panel-pad h-100">
                <h2 class="gr-h2">Pendapatan 6 bulan terakhir</h2>
                <?php if ($maks_bulan > 0): ?>
                    <?php
                    $ringkas_aria = [];
                    foreach ($enam as $kunci => $nilai) {
                        $ringkas_aria[] = BULAN_PENDEK[(int)substr($kunci, 5, 2) - 1] . ' ' . substr($kunci, 0, 4) . ' ' . rupiah($nilai);
                    }
                    ?>
                    <div class="gr-grafik" role="img" aria-label="Pendapatan per bulan: <?= h(implode(', ', $ringkas_aria)); ?>">
                        <?php foreach ($enam as $kunci => $nilai): ?>
                            <div class="kolom <?= $kunci === $bulan_ini ? 'sekarang' : ''; ?>">
                                <div class="nilai"><?= $nilai > 0 ? rupiahSingkat($nilai) : '-'; ?></div>
                                <div class="batang" style="height: <?= max(3, (int)round($nilai / $maks_bulan * $maks_px)); ?>px"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="gr-label-bulan" aria-hidden="true">
                        <?php foreach ($enam as $kunci => $nilai): ?>
                            <span><?= BULAN_PENDEK[(int)substr($kunci, 5, 2) - 1]; ?><?= substr($kunci, 5, 2) === '01' ? ' ' . substr($kunci, 2, 2) : ''; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-secondary mb-0">Belum ada pekerjaan dalam 6 bulan terakhir, jadi grafik belum bisa ditampilkan.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="gr-panel gr-panel-pad h-100">
                <h2 class="gr-h2">Rincian pendapatan</h2>

                <div class="gr-sector mb-3" role="img" aria-label="Servis <?= $persen_servis; ?> persen, restorasi <?= $persen_restorasi; ?> persen">
                    <span class="seg seg-a" style="width: <?= $persen_servis; ?>%"></span>
                    <span class="seg seg-b" style="width: <?= $persen_restorasi; ?>%"></span>
                </div>

                <div class="d-flex flex-column gap-3">
                    <div class="gr-legend">
                        <i class="sw sw-a"></i>
                        <div>
                            <div class="gr-legend-name">Servis</div>
                            <div class="gr-legend-val"><?= rupiah($pendapatan_servis); ?><small><?= $persen_servis; ?>%</small></div>
                        </div>
                    </div>
                    <div class="gr-legend">
                        <i class="sw sw-b"></i>
                        <div>
                            <div class="gr-legend-name">Restorasi</div>
                            <div class="gr-legend-val"><?= rupiah($pendapatan_restorasi); ?><small><?= $persen_restorasi; ?>%</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <h2 class="gr-h2">Pekerjaan yang belum selesai</h2>
            <div class="gr-panel">
                <div class="table-responsive">
                    <table class="table table-gr table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kendaraan</th>
                                <th>Pekerjaan</th>
                                <th>Masuk</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($berjalan) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($berjalan)): ?>
                                    <tr>
                                        <td class="kol-id">
                                            <a class="text-reset text-decoration-none" href="<?= $r['jenis'] === 'Servis' ? 'servis_detail.php?id=' . urlencode($r['id']) : 'restorasi.php?cari=' . urlencode($r['id']); ?>"><?= h($r['id']); ?></a>
                                            <div class="kol-sub"><?= h($r['jenis']); ?></div>
                                        </td>
                                        <td class="kol-kendaraan">
                                            <?= h($r['merk']); ?>
                                            <div class="kol-sub"><?= h($r['nopol']); ?> - <?= $r['nama_mekanik'] ? h($r['nama_mekanik']) : 'belum ada mekanik'; ?></div>
                                        </td>
                                        <td class="kol-uraian"><?= h($r['uraian']); ?></td>
                                        <td class="kol-tgl"><?= tanggalIndo($r['tanggal_masuk']); ?></td>
                                        <td><span class="st <?= kelasStatus($r['status']); ?>"><?= h($r['status']); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="gr-empty">Tidak ada pekerjaan yang tertunda. Semua sudah selesai.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <h2 class="gr-h2">Stok perlu dibeli</h2>
            <div class="gr-panel gr-panel-pad">
                <?php if (mysqli_num_rows($menipis) > 0): ?>
                    <ul class="gr-daftar">
                        <?php while ($p = mysqli_fetch_assoc($menipis)): ?>
                            <?php [$kelas_stok, $teks_stok] = kondisiStok($p['stok'], $p['stok_minimum']); ?>
                            <li>
                                <span><?= h($p['nama']); ?><small>Sisa <?= (int)$p['stok']; ?> <?= h($p['satuan']); ?> (minimum <?= (int)$p['stok_minimum']; ?>)</small></span>
                                <span class="st <?= $kelas_stok; ?>"><?= $teks_stok; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                    <a href="sparepart.php?kondisi=menipis" class="btn btn-secondary btn-sm mt-3">Lihat semua</a>
                <?php else: ?>
                    <p class="text-secondary mb-0">Semua suku cadang di atas batas minimum.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2 class="gr-h2">Akses cepat</h2>
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="pelanggan.php">
                <strong>Pelanggan</strong>
                <span>Tambah, cari, dan ubah pemilik</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="kendaraan.php">
                <strong>Kendaraan</strong>
                <span>Kendaraan dan riwayat servisnya</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="servis.php">
                <strong>Servis</strong>
                <span>Keluhan, suku cadang, dan nota</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="restorasi.php">
                <strong>Restorasi</strong>
                <span>Pekerjaan dan biaya</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="sparepart.php">
                <strong>Sparepart</strong>
                <span>Stok dan harga suku cadang</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="mekanik.php">
                <strong>Mekanik</strong>
                <span>Daftar mekanik dan beban kerja</span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="gr-link-card" href="laporan.php">
                <strong>Laporan</strong>
                <span>Pendapatan per periode, unduh CSV</span>
            </a>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
