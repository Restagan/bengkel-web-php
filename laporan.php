<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

// ---------- Rentang tanggal (berdasarkan tanggal masuk) ----------
$awal_bulan = date('Y-m-01');
$dari       = tanggalValid($_GET['dari'] ?? '') ? $_GET['dari'] : $awal_bulan;
$sampai     = tanggalValid($_GET['sampai'] ?? '') ? $_GET['sampai'] : hariIni();
if ($dari > $sampai) { [$dari, $sampai] = [$sampai, $dari]; }

$f_jenis = pilihan($_GET['jenis'] ?? '', ['', 'Servis', 'Restorasi'], '');
$f_bayar = pilihan($_GET['bayar'] ?? '', array_merge([''], STATUS_BAYAR), '');

$syarat = "kerja.tanggal_masuk BETWEEN '$dari' AND '$sampai'";
if ($f_jenis !== '') { $syarat .= " AND kerja.jenis = '$f_jenis'"; }
if ($f_bayar !== '') { $syarat .= " AND kerja.status_bayar = '$f_bayar'"; }

$result = mysqli_query($conn, "SELECT kerja.*, kendaraan.merk, kendaraan.nopol,
        pelanggan.nama AS nama_pelanggan, mekanik.nama AS nama_mekanik
    FROM (
        SELECT 'Servis' AS jenis, id, id_kendaraan, id_mekanik, keluhan AS uraian, status, status_bayar, biaya, tanggal_masuk, tanggal_selesai
            FROM servis
        UNION ALL
        SELECT 'Restorasi', id, id_kendaraan, id_mekanik, deskripsi, status, status_bayar, biaya, tanggal_masuk, tanggal_selesai
            FROM restorasi
    ) AS kerja
    JOIN kendaraan ON kerja.id_kendaraan = kendaraan.id
    JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
    LEFT JOIN mekanik ON kerja.id_mekanik = mekanik.id
    WHERE $syarat
    ORDER BY kerja.tanggal_masuk DESC, LENGTH(kerja.id) DESC, kerja.id DESC");

$baris = [];
while ($r = mysqli_fetch_assoc($result)) { $baris[] = $r; }

// ---------- Ekspor CSV (bisa dibuka di Excel) ----------
if (isset($_GET['ekspor'])) {
    // Sel yang diawali = + - @ bisa dibaca Excel sebagai rumus, jadi diberi tanda petik di depannya
    $aman = function ($teks) {
        $teks = (string)$teks;
        return ($teks !== '' && strpos("=+-@\t\r", $teks[0]) !== false) ? "'" . $teks : $teks;
    };

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan-bengkel_' . $dari . '_sd_' . $sampai . '.csv"');
    echo "\xEF\xBB\xBF";          // BOM agar Excel membaca UTF-8
    echo "sep=;\n";               // Excel memakai titik koma sebagai pemisah, apa pun pengaturan bahasanya
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tanggal masuk', 'Tanggal selesai', 'Jenis', 'ID', 'Nopol', 'Kendaraan', 'Pelanggan', 'Mekanik', 'Pekerjaan', 'Status', 'Pembayaran', 'Biaya (Rp)'], ';');
    foreach ($baris as $r) {
        fputcsv($out, [
            $r['tanggal_masuk'], $r['tanggal_selesai'], $r['jenis'], $r['id'], $aman($r['nopol']), $aman($r['merk']),
            $aman($r['nama_pelanggan']), $aman($r['nama_mekanik']), $aman($r['uraian']),
            $r['status'], $r['status_bayar'], digitUang($r['biaya']),
        ], ';');
    }
    fclose($out);
    exit;
}

// ---------- Ringkasan ----------
$total = $lunas = $belum = 0;
$jml   = ['Servis' => 0, 'Restorasi' => 0];
$per_mekanik = [];
foreach ($baris as $r) {
    $total += $r['biaya'];
    if ($r['status_bayar'] === 'Lunas') { $lunas += $r['biaya']; } else { $belum += $r['biaya']; }
    $jml[$r['jenis']]++;

    $nama = $r['nama_mekanik'] ?? 'Tanpa mekanik';
    if (!isset($per_mekanik[$nama])) { $per_mekanik[$nama] = ['jumlah' => 0, 'biaya' => 0]; }
    $per_mekanik[$nama]['jumlah']++;
    $per_mekanik[$nama]['biaya'] += $r['biaya'];
}
uasort($per_mekanik, function ($a, $b) { return $b['biaya'] <=> $a['biaya']; });

// Jalan pintas rentang tanggal
$jalan_pintas = [
    'Bulan ini'  => [$awal_bulan, hariIni()],
    'Bulan lalu' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
    'Tahun ini'  => [date('Y-01-01'), hariIni()],
    'Semua'      => ['2000-01-01', hariIni()],
];
$sisa_param = http_build_query(['jenis' => $f_jenis, 'bayar' => $f_bayar]);
$param_ekspor = http_build_query(['dari' => $dari, 'sampai' => $sampai, 'jenis' => $f_jenis, 'bayar' => $f_bayar, 'ekspor' => 1]);

$judul_halaman = 'Laporan';
$menu_aktif    = 'laporan';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Laporan pendapatan</h1>
        <a href="laporan.php?<?= h($param_ekspor); ?>" class="btn btn-primary">Unduh CSV (Excel)</a>
    </div>

    <?= tampilFlash(); ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2 align-items-end">
            <div class="col-lg-2 col-md-6">
                <label class="form-label" for="dari">Dari tanggal</label>
                <input type="date" name="dari" id="dari" class="form-control" value="<?= h($dari); ?>" required>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label" for="sampai">Sampai tanggal</label>
                <input type="date" name="sampai" id="sampai" class="form-control" value="<?= h($sampai); ?>" required>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label" for="jenis">Jenis</label>
                <select name="jenis" id="jenis" class="form-select">
                    <option value="">Semua jenis</option>
                    <option value="Servis" <?= $f_jenis === 'Servis' ? 'selected' : ''; ?>>Servis saja</option>
                    <option value="Restorasi" <?= $f_jenis === 'Restorasi' ? 'selected' : ''; ?>>Restorasi saja</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="bayar">Pembayaran</label>
                <select name="bayar" id="bayar" class="form-select">
                    <option value="">Semua</option>
                    <?php foreach (STATUS_BAYAR as $s): ?>
                        <option value="<?= $s; ?>" <?= $f_bayar === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <button type="submit" class="btn btn-secondary w-100">Tampilkan</button>
            </div>
        </div>
        <div class="gr-pintas">
            <?php foreach ($jalan_pintas as $nama => $rentang): ?>
                <a href="laporan.php?<?= h('dari=' . $rentang[0] . '&sampai=' . $rentang[1] . '&' . $sisa_param); ?>"><?= $nama; ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <p class="gr-catatan">Periode <strong><?= tanggalIndo($dari); ?></strong> sampai <strong><?= tanggalIndo($sampai); ?></strong>, dihitung berdasarkan tanggal masuk pekerjaan.</p>

    <div class="gr-tiles mb-4">
        <div class="gr-tile">
            <span>Total tagihan</span>
            <strong><?= rupiah($total); ?></strong>
            <small><?= count($baris); ?> pekerjaan (<?= $jml['Servis']; ?> servis, <?= $jml['Restorasi']; ?> restorasi)</small>
        </div>
        <div class="gr-tile gr-tile-hijau">
            <span>Sudah dibayar</span>
            <strong><?= rupiah($lunas); ?></strong>
            <small>Uang yang sudah masuk</small>
        </div>
        <div class="gr-tile <?= $belum > 0 ? 'gr-tile-merah' : ''; ?>">
            <span>Belum dibayar</span>
            <strong><?= rupiah($belum); ?></strong>
            <small><?= $belum > 0 ? 'Perlu ditagih' : 'Tidak ada tunggakan'; ?></small>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-9">
            <div class="gr-panel">
                <div class="table-responsive">
                    <table class="table table-gr table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>ID</th>
                                <th>Kendaraan</th>
                                <th>Pekerjaan</th>
                                <th>Biaya</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($baris): ?>
                                <?php foreach ($baris as $r): ?>
                                    <tr>
                                        <td class="kol-tgl"><?= tanggalIndo($r['tanggal_masuk']); ?></td>
                                        <td class="kol-id"><?= h($r['id']); ?><div class="kol-sub"><?= h($r['jenis']); ?></div></td>
                                        <td class="kol-kendaraan">
                                            <?= h($r['merk']); ?>
                                            <div class="kol-sub"><?= h($r['nopol']); ?> - <?= h($r['nama_pelanggan']); ?></div>
                                        </td>
                                        <td class="kol-uraian"><?= h($r['uraian']); ?></td>
                                        <td class="kol-rp"><?= rupiah($r['biaya']); ?></td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start gap-1">
                                                <span class="st <?= kelasStatus($r['status']); ?>"><?= h($r['status']); ?></span>
                                                <span class="st <?= kelasBayar($r['status_bayar']); ?>"><?= h($r['status_bayar']); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="gr-empty">Tidak ada pekerjaan pada periode dan filter ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if ($baris): ?>
                            <tfoot>
                                <tr class="baris-total">
                                    <td colspan="4" class="text-end">Total</td>
                                    <td class="kol-rp"><?= rupiah($total); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="gr-panel gr-panel-pad">
                <h2 class="gr-h2">Per mekanik</h2>
                <?php if ($per_mekanik): ?>
                    <ul class="gr-daftar">
                        <?php foreach ($per_mekanik as $nama => $d): ?>
                            <li>
                                <span><?= h($nama); ?><small><?= $d['jumlah']; ?> pekerjaan</small></span>
                                <strong><?= rupiah($d['biaya']); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-secondary mb-0">Belum ada data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
