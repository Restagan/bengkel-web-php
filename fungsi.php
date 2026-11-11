<?php
const NAMA_BENGKEL   = 'Bengkel System';
const ALAMAT_BENGKEL = 'Jl. Contoh No. 1, Bandung';
const TELP_BENGKEL   = '0812-0000-0000';

const STATUS_KERJA = ['Menunggu', 'Dikerjakan', 'Selesai'];
const STATUS_BAYAR = ['Belum Lunas', 'Lunas'];
const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

function h($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function potong($teks, $maks) {
    $teks = trim((string)$teks);
    return function_exists('mb_substr') ? mb_substr($teks, 0, $maks, 'UTF-8') : substr($teks, 0, $maks);
}

function dariPost($conn, $kunci, $maks = 255) {
    return mysqli_real_escape_string($conn, potong($_POST[$kunci] ?? '', $maks));
}

function dariGet($conn, $kunci, $maks = 100) {
    return mysqli_real_escape_string($conn, potong($_GET[$kunci] ?? '', $maks));
}

function pilihan($nilai, $daftar, $bawaan) {
    return in_array($nilai, $daftar, true) ? $nilai : $bawaan;
}

function telpValid($telp) {
    return (bool)preg_match('/^[0-9+\-\s()]{5,20}$/', trim((string)$telp));
}

function rapikanNopol($nopol) {
    return strtoupper(preg_replace('/\s+/', ' ', trim((string)$nopol)));
}

function nopolValid($nopol) {
    return (bool)preg_match('/^[A-Z0-9\- ]{3,20}$/', $nopol);
}

function angka($nilai) {
    $teks  = preg_replace('/,\d{1,2}$/', '', trim((string)$nilai));
    $digit = ltrim(preg_replace('/\D/', '', $teks), '0');
    return $digit === '' ? '0' : $digit;
}

function nominalValid($digit) {
    return strlen($digit) <= 13;
}

function bilangan($nilai, $maks = 999999) {
    $digit = angka($nilai);
    return strlen($digit) > strlen((string)$maks) ? $maks : min((int)$digit, $maks);
}

function digitUang($nilai) {
    return number_format((float)$nilai, 0, '', '');
}

function rupiah($nilai) {
    return 'Rp ' . number_format((float)$nilai, 0, ',', '.');
}

function rupiahSingkat($nilai) {
    $n = (float)$nilai;
    if ($n >= 1e9) return str_replace('.', ',', (string)round($n / 1e9, 1)) . ' M';
    if ($n >= 1e6) return str_replace('.', ',', (string)round($n / 1e6, 1)) . ' jt';
    if ($n >= 1e3) return str_replace('.', ',', (string)round($n / 1e3, 1)) . ' rb';
    return (string)(int)$n;
}

function hariIni() {
    return date('Y-m-d');
}

function tanggalValid($teks) {
    return (bool)preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$teks, $m)
        && checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

function tanggalIndo($ymd) {
    if (!tanggalValid($ymd)) return '-';
    [$tahun, $bulan, $hari] = explode('-', $ymd);
    return (int)$hari . ' ' . BULAN_PENDEK[(int)$bulan - 1] . ' ' . $tahun;
}

function tanggalDariPost($kunci) {
    $tanggal = $_POST[$kunci] ?? '';
    return tanggalValid($tanggal) ? $tanggal : hariIni();
}


function pindah($url) {
    header('Location: ' . $url);
    exit;
}

function flash($pesan, $tipe = 'success') {
    $_SESSION['flash'] = ['pesan' => $pesan, 'tipe' => $tipe];
}

function tampilFlash() {
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $kelas = ['danger' => 'alert-danger', 'warning' => 'alert-warning'][$f['tipe']] ?? 'alert-success';
    return '<div class="alert ' . $kelas . ' py-2" role="alert">' . h($f['pesan']) . '</div>';
}

function kelasStatus($status) {
    $s = strtolower(trim((string)$status));
    if ($s === 'selesai')    return 'st-selesai';
    if ($s === 'dikerjakan') return 'st-dikerjakan';
    return 'st-menunggu';
}

function kelasBayar($status) {
    return $status === 'Lunas' ? 'st-lunas' : 'st-belum';
}

function kondisiStok($stok, $minimum) {
    if ($stok <= 0)        return ['st-habis', 'Habis'];
    if ($stok <= $minimum) return ['st-menipis', 'Menipis'];
    return ['st-aman', 'Aman'];
}

function idBerikutnya($conn, $tabel, $awalan) {
    $hasil = mysqli_query($conn, "SELECT id FROM $tabel WHERE id LIKE '$awalan%'");
    $pola  = '/^' . preg_quote($awalan, '/') . '(\d+)$/i';
    $maks  = 0;
    while ($baris = mysqli_fetch_assoc($hasil)) {
        if (preg_match($pola, $baris['id'], $m)) $maks = max($maks, (int)$m[1]);
    }
    return $awalan . str_pad((string)($maks + 1), 3, '0', STR_PAD_LEFT);
}

function periksaPekerjaan($conn, $id_kendaraan, $id_mekanik, $uraian, $biaya, $nama_uraian) {
    if ($id_kendaraan === '' || $uraian === '') {
        return "Kendaraan dan $nama_uraian wajib diisi.";
    }
    $kendaraan = mysqli_query($conn, "SELECT id FROM kendaraan WHERE id = '$id_kendaraan'");
    if (mysqli_num_rows($kendaraan) == 0) {
        return 'Kendaraan tidak ditemukan. Pilih kendaraan dari daftar.';
    }
    if ($id_mekanik !== '') {
        $mekanik = mysqli_query($conn, "SELECT id FROM mekanik WHERE id = '$id_mekanik'");
        if (mysqli_num_rows($mekanik) == 0) {
            return 'Mekanik tidak ditemukan. Pilih mekanik dari daftar.';
        }
    }
    if (!nominalValid($biaya)) {
        return 'Biaya terlalu besar.';
    }
    return '';
}

function sqlMekanik($id_mekanik) {
    return $id_mekanik === '' ? 'NULL' : "'$id_mekanik'";
}

function sqlTanggalSelesai($status) {
    return $status === 'Selesai' ? "COALESCE(tanggal_selesai, '" . hariIni() . "')" : 'NULL';
}

function hitungUlangBiayaServis($conn, $idServis) {
    mysqli_query($conn, "UPDATE servis SET biaya = biaya_jasa + (
        SELECT COALESCE(SUM(jumlah * harga_satuan), 0) FROM servis_item WHERE servis_item.id_servis = servis.id
    ) WHERE id = '$idServis'");
}

function kembalikanStokServis($conn, $idServis) {
    mysqli_query($conn, "UPDATE sparepart SET stok = stok + (
        SELECT COALESCE(SUM(jumlah), 0) FROM servis_item
        WHERE servis_item.id_servis = '$idServis' AND servis_item.id_sparepart = sparepart.id
    ) WHERE id IN (SELECT id_sparepart FROM servis_item WHERE id_servis = '$idServis')");
}

function kembalikanStokServisBelumSelesai($conn, $syarat) {
    $hasil = mysqli_query($conn, "SELECT id FROM servis WHERE status <> 'Selesai' AND ($syarat)");
    while ($baris = mysqli_fetch_assoc($hasil)) {
        kembalikanStokServis($conn, mysqli_real_escape_string($conn, $baris['id']));
    }
}

function periksaSkema($conn) {
    $sesi = session_status() === PHP_SESSION_ACTIVE;
    if ($sesi && !empty($_SESSION['skema_ok'])) return;

    $uji = [
        'tabel mekanik'     => 'SELECT id, nama, telp, spesialis FROM mekanik LIMIT 0',
        'tabel sparepart'   => 'SELECT id, nama, satuan, harga, stok, stok_minimum FROM sparepart LIMIT 0',
        'tabel servis_item' => 'SELECT id, id_servis, id_sparepart, jumlah, harga_satuan FROM servis_item LIMIT 0',
        'kolom baru pada tabel servis (id_mekanik, biaya_jasa, status_bayar, tanggal_masuk, tanggal_selesai)'
            => 'SELECT id_mekanik, biaya_jasa, status_bayar, tanggal_masuk, tanggal_selesai FROM servis LIMIT 0',
        'kolom baru pada tabel restorasi (id_mekanik, status_bayar, tanggal_masuk, tanggal_selesai)'
            => 'SELECT id_mekanik, status_bayar, tanggal_masuk, tanggal_selesai FROM restorasi LIMIT 0',
    ];
    $kurang = [];
    foreach ($uji as $nama => $sql) {
        try { $hasil = @mysqli_query($conn, $sql); } catch (Throwable $e) { $hasil = false; }
        if (!$hasil) $kurang[] = $nama;
    }
    if (!$kurang) {
        if ($sesi) $_SESSION['skema_ok'] = true;
        return;
    }

    http_response_code(500);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database belum diperbarui</title>
    <style>
        body { margin: 0; padding: 24px 16px; background: #0a0a0c; color: #f4f4f5; font: 16px/1.6 "Segoe UI", Arial, sans-serif; }
        main { max-width: 680px; margin: 0 auto; padding: 24px 28px; background: #141418; border: 1px solid #2a2a31; border-top: 4px solid #eb0a1e; }
        h1 { margin: 0 0 8px; font-size: 24px; font-style: italic; text-transform: uppercase; }
        h2 { margin: 24px 0 6px; font-size: 17px; }
        code { padding: 1px 6px; background: #0c0c0f; border: 1px solid #2a2a31; }
        li { margin-bottom: 4px; }
        .abu { color: #9b9ba5; }
    </style>
</head>
<body>
<main>
    <h1>Database belum diperbarui</h1>
    <p>Program ini memakai tabel dan kolom baru yang belum ada di database <code>db_bengkel</code> Anda. Yang belum ada:</p>
    <ul>
        <?php foreach ($kurang as $k): ?><li><?= h($k); ?></li><?php endforeach; ?>
    </ul>

    <h2>Pilihan 1: data lama ingin dipertahankan</h2>
    <ol>
        <li>Buka <code>http://localhost/phpmyadmin</code> lalu klik database <code>db_bengkel</code>.</li>
        <li>Klik tab <b>SQL</b>, tempel seluruh isi file <code>migrasi.sql</code>, lalu klik <b>Kirim</b>.</li>
        <li>Jalankan <b>sekali saja</b>, lalu muat ulang halaman ini.</li>
    </ol>

    <h2>Pilihan 2: data boleh dikosongkan</h2>
    <ol>
        <li>Di phpMyAdmin, hapus database <code>db_bengkel</code> (tab <b>Operasi</b>, <b>Hapus database</b>).</li>
        <li>Klik tab <b>Import</b>, pilih file <code>database.sql</code>, lalu klik <b>Import</b>.</li>
        <li>Muat ulang halaman ini.</li>
    </ol>
    <p class="abu">Kedua file ada di folder proyek <code>bengkel-web</code>.</p>
</main>
</body>
</html>
<?php
    exit;
}

periksaSkema($conn);
