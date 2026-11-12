<?php
session_start();
require 'koneksi.php';

const LACAK_BATAS_GAGAL = 8;
const LACAK_JEDA        = 300;
const LACAK_SELANG      = 30;

header('Cache-Control: no-store');

$input    = potong($_GET['q'] ?? '', 30);
$mode     = '';
$pesan    = '';
$jenis    = 'warning';
$hasil    = [];
$sekarang = time();

$gagal = array_values(array_filter($_SESSION['lacak_gagal'] ?? [], function ($t) use ($sekarang) {
    return $t > $sekarang - LACAK_JEDA;
}));

if ($input !== '') {
    $norm = strtoupper(preg_replace('/\s+/', '', $input));

    if (count($gagal) >= LACAK_BATAS_GAGAL) {
        $pesan = 'Terlalu banyak pencarian yang tidak ditemukan. Demi keamanan, coba lagi sekitar 5 menit lagi.';
    } elseif (preg_match('/^SRV-?([A-Z0-9]{6})$/', $norm, $m)) {
        $mode = 'resi';
        $kode = 'SRV-' . $m[1];
        $kunci = mysqli_real_escape_string($conn, $kode);
        $syarat = "servis.kode_resi = '$kunci'";
    } elseif (preg_match('/^[A-Z0-9-]{3,20}$/', $norm) && preg_match('/[0-9]/', $norm)) {
        $mode = 'nopol';
        $kunci = mysqli_real_escape_string($conn, str_replace('-', '', $norm));
        $syarat = "REPLACE(REPLACE(UPPER(kendaraan.nopol), ' ', ''), '-', '') = '$kunci'";
    } else {
        $pesan = 'Format tidak dikenali. Isi nomor polisi (contoh: D 1234 GR) atau kode resi (contoh: SRV-K7M2XQ).';
    }

    if ($mode !== '') {
        $query = mysqli_query($conn, "SELECT servis.id, servis.kode_resi, servis.keluhan, servis.status, servis.status_bayar,
                servis.biaya, servis.tanggal_masuk, servis.tanggal_selesai,
                kendaraan.merk, kendaraan.nopol, mekanik.nama AS nama_mekanik
            FROM servis
            JOIN kendaraan ON servis.id_kendaraan = kendaraan.id
            LEFT JOIN mekanik ON servis.id_mekanik = mekanik.id
            WHERE $syarat
            ORDER BY CASE WHEN servis.status = 'Selesai' THEN 1 ELSE 0 END ASC,
                servis.tanggal_masuk DESC, LENGTH(servis.id) DESC, servis.id DESC
            LIMIT 5");
        while ($baris = mysqli_fetch_assoc($query)) { $hasil[] = $baris; }

        if (!$hasil) {
            $gagal[] = $sekarang;
            $jenis = 'danger';
            $pesan = $mode === 'resi'
                ? 'Kode resi tidak ditemukan. Periksa kembali kode pada nota servis Anda.'
                : 'Tidak ada servis untuk nopol tersebut. Periksa kembali nopolnya, atau gunakan kode resi dari nota.';
        }
    }
}
$_SESSION['lacak_gagal'] = $gagal;

$tanda = implode('|', array_map(function ($r) { return $r['status'] . '/' . $r['status_bayar']; }, $hasil));

if (isset($_GET['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['tanda' => $tanda]);
    exit;
}

$langkah = [
    'Menunggu'   => 'Kendaraan Anda sudah kami terima dan menunggu giliran untuk dikerjakan.',
    'Dikerjakan' => 'Mekanik sedang mengerjakan kendaraan Anda.',
    'Selesai'    => 'Servis selesai. Kendaraan siap diambil.',
];
$persen = [33, 67, 100];

$masih_berjalan = false;
foreach ($hasil as $r) { if ($r['status'] !== 'Selesai') { $masih_berjalan = true; } }

$judul_halaman = 'Lacak Servis';
$tanpa_navbar  = true;
$body_class    = 'gr-lacak';
include 'partials/header.php';
?>

<header class="gr-navbar lacak-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="gr-brand" href="lacak.php">
            <?php if ($logo_ada): ?><img src="assets/img/logo.png" alt="" class="gr-logo"><?php endif; ?>
            <span class="gr-mark" aria-hidden="true"></span>
            <span><?= h(NAMA_BENGKEL); ?></span>
        </a>
        <a href="index.php" class="lacak-admin">Login admin</a>
    </div>
</header>
<div class="gr-stripe" aria-hidden="true"></div>

<div class="container py-4 py-lg-5">
    <div class="lacak-lebar">
        <h1 class="gr-title">Lacak servis kendaraan</h1>
        <p class="gr-sub">Pantau progres kendaraan Anda di bengkel, kapan saja.</p>

        <form method="get" class="lacak-form" role="search">
            <label class="form-label" for="q">Nomor polisi atau Kode Resi</label>
            <div class="row g-2">
                <div class="col-12 col-sm">
                    <input type="text" id="q" name="q" class="form-control form-control-lg" required maxlength="30"
                        value="<?= h($input); ?>" placeholder="D 1234 GR atau SRV-K7M2XQ"
                        autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus>
                </div>
                <div class="col-12 col-sm-auto">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Lacak</button>
                </div>
            </div>
            <div class="form-text">Kode resi tertera di nota servis Anda. Dengan kode resi, rincian servis ikut tampil.</div>
        </form>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-<?= $jenis; ?> py-2" role="alert"><?= h($pesan); ?></div>
        <?php elseif ($input === ''): ?>
            <div class="gr-panel gr-panel-pad lacak-cara">
                <h2 class="gr-h2">Cara melacak</h2>
                <ol class="mb-0">
                    <li>Ketik <strong>nomor polisi</strong> kendaraan atau <strong>kode resi</strong> dari nota.</li>
                    <li>Lihat tahap pengerjaan: <strong>Menunggu</strong>, <strong>Dikerjakan</strong>, lalu <strong>Selesai</strong>.</li>
                    <li>Halaman ini memeriksa pembaruan otomatis, tidak perlu dimuat ulang.</li>
                </ol>
            </div>
        <?php endif; ?>

        <?php if ($hasil): ?>
            <div id="lacakHasil" class="mt-4"
                 <?= $masih_berjalan ? 'data-poll="1"' : ''; ?>
                 data-selang="<?= LACAK_SELANG; ?>"
                 data-tanda="<?= h($tanda); ?>"
                 data-json="<?= h('lacak.php?q=' . urlencode($input) . '&json=1'); ?>">

                <?php if ($mode === 'nopol'): ?>
                    <p class="gr-catatan">Menampilkan <?= count($hasil); ?> servis terbaru untuk kendaraan <strong><?= h(samarkanNopol($hasil[0]['nopol'])); ?></strong>.
                        Untuk melihat rincian, cari dengan kode resi dari nota.</p>
                <?php endif; ?>

                <?php foreach ($hasil as $r): ?>
                    <?php
                    $idx = array_search($r['status'], STATUS_KERJA, true);
                    $idx = $idx === false ? 0 : $idx;
                    $selesai_semua = $idx === 2;
                    $kelas_pesan   = ['menunggu', 'dikerjakan', 'selesai'][$idx];
                    ?>
                    <article class="gr-panel gr-panel-pad lacak-kartu s-<?= $kelas_pesan; ?>">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h2 class="lacak-merk"><?= h($r['merk']); ?></h2>
                                <div class="kol-sub"><?= h(samarkanNopol($r['nopol'])); ?></div>
                            </div>
                            <?php if ($mode === 'resi'): ?>
                                <div class="lacak-kode" title="Kode resi"><?= h($r['kode_resi']); ?></div>
                            <?php else: ?>
                                <span class="st <?= kelasStatus($r['status']); ?>"><?= h($r['status']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between small text-secondary mb-1">
                            <span>Progres servis</span><span><?= $persen[$idx]; ?>%</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Progres servis"
                             aria-valuenow="<?= $persen[$idx]; ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar <?= $idx === 1 ? 'progress-bar-striped progress-bar-animated' : ''; ?>"
                                 style="width: <?= $persen[$idx]; ?>%"></div>
                        </div>

                        <ol class="gr-langkah" aria-label="Tahap servis">
                            <?php foreach (STATUS_KERJA as $i => $nama): ?>
                                <?php
                                $kelas = $i < $idx || $selesai_semua ? 'selesai' : ($i === $idx ? 'aktif' : 'belum');
                                if ($i === 2) { $kelas .= ' akhir'; }
                                $tgl = '';
                                if ($i === 0) { $tgl = 'Masuk ' . tanggalIndo($r['tanggal_masuk']); }
                                if ($i === 1 && $idx === 1) { $tgl = 'Sedang berjalan'; }
                                if ($i === 2 && $selesai_semua && $r['tanggal_selesai']) { $tgl = tanggalIndo($r['tanggal_selesai']); }
                                ?>
                                <li class="<?= $kelas; ?>"<?= $i === $idx ? ' aria-current="step"' : ''; ?>>
                                    <span class="titik" aria-hidden="true"><?= ($i < $idx || $selesai_semua) ? '&#10003;' : $i + 1; ?></span>
                                    <div class="teks">
                                        <strong><?= h($nama); ?></strong>
                                        <small><?= $tgl !== '' ? h($tgl) : '&nbsp;'; ?></small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>

                        <div class="lacak-pesan <?= $kelas_pesan; ?>">
                            <?= h($langkah[STATUS_KERJA[$idx]]); ?>
                            <?php if ($mode === 'resi' && $selesai_semua && $r['status_bayar'] !== 'Lunas'): ?>
                                Silakan selesaikan pembayaran di kasir saat mengambil kendaraan.
                            <?php endif; ?>
                        </div>

                        <?php if ($mode === 'resi'): ?>
                            <dl class="gr-dl mt-4 mb-0">
                                <dt>Keluhan</dt>
                                <dd><?= h($r['keluhan']); ?></dd>
                                <dt>Mekanik</dt>
                                <dd><?= $r['nama_mekanik'] ? h($r['nama_mekanik']) : '<span class="text-secondary">Belum ditentukan</span>'; ?></dd>
                                <dt>Total biaya</dt>
                                <dd><?= rupiah($r['biaya']); ?></dd>
                                <dt>Pembayaran</dt>
                                <dd><span class="st <?= kelasBayar($r['status_bayar']); ?>"><?= h($r['status_bayar']); ?></span></dd>
                            </dl>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>

                <?php if ($masih_berjalan): ?>
                    <p class="lacak-live" aria-live="polite">
                        <i aria-hidden="true"></i>
                        <span>Live: diperiksa otomatis tiap <?= LACAK_SELANG; ?> detik. Terakhir diperiksa <span id="lacakJam"><?= date('H:i:s'); ?></span></span>
                    </p>
                <?php endif; ?>
            </div>
            <script src="assets/js/lacak.js"></script>
        <?php endif; ?>

        <p class="lacak-privasi">Demi privasi, pencarian dengan nopol hanya menampilkan status pengerjaan. Data pemilik tidak pernah ditampilkan di halaman ini.</p>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
