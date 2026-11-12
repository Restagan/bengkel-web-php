<?php
/*
 * Fungsi bantu yang dipakai semua halaman.
 * Dimuat otomatis oleh koneksi.php, jadi tidak perlu di-require lagi.
 */

// ---------- Data bengkel (tampil di nota cetak). Ubah sesuai kebutuhan ----------
const NAMA_BENGKEL   = 'Bengkel System';
const ALAMAT_BENGKEL = 'Jl. Contoh No. 1, Bandung';
const TELP_BENGKEL   = '0812-0000-0000';

const STATUS_KERJA = ['Menunggu', 'Dikerjakan', 'Selesai'];
const STATUS_BAYAR = ['Belum Lunas', 'Lunas'];
// Naikkan angka ini jika skema database berubah lagi, supaya pemeriksaan dijalankan ulang di sesi yang sudah login
const SKEMA_VERSI  = 2;
const BATAS_STOK_KRITIS = 3;
const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// ---------- Input dan output yang aman ----------

// Mengamankan teks yang tampil di HTML (juga di dalam atribut seperti data-nama="...")
function h($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Rapikan spasi dan batasi panjang teks supaya muat di kolom database
function potong($teks, $maks) {
    $teks = trim((string)$teks);
    return function_exists('mb_substr') ? mb_substr($teks, 0, $maks, 'UTF-8') : substr($teks, 0, $maks);
}

// Isian form (POST) yang aman dimasukkan ke query, tanda petik tidak lagi merusak query
function dariPost($conn, $kunci, $maks = 255) {
    return mysqli_real_escape_string($conn, potong($_POST[$kunci] ?? '', $maks));
}

// Parameter URL (GET) yang aman dimasukkan ke query
function dariGet($conn, $kunci, $maks = 100) {
    return mysqli_real_escape_string($conn, potong($_GET[$kunci] ?? '', $maks));
}

// Nilai pilihan (status, urutan, dll) hanya boleh dari daftar yang diizinkan
function pilihan($nilai, $daftar, $bawaan) {
    return in_array($nilai, $daftar, true) ? $nilai : $bawaan;
}

// No. telepon: 5-20 karakter berisi angka, spasi, +, -, dan tanda kurung
function telpValid($telp) {
    return (bool)preg_match('/^[0-9+\-\s()]{5,20}$/', trim((string)$telp));
}

// "d  1234 gr" menjadi "D 1234 GR"
function rapikanNopol($nopol) {
    return strtoupper(preg_replace('/\s+/', ' ', trim((string)$nopol)));
}

function nopolValid($nopol) {
    return (bool)preg_match('/^[A-Z0-9\- ]{3,20}$/', $nopol);
}

// ---------- Angka, uang, dan tanggal ----------

// "1.250.000" atau "Rp 1250000" menjadi "1250000" (hanya digit).
// Sen di belakang koma dibuang: "2.500.000,00" menjadi "2500000".
function angka($nilai) {
    $teks  = preg_replace('/,\d{1,2}$/', '', trim((string)$nilai));
    $digit = ltrim(preg_replace('/\D/', '', $teks), '0');
    return $digit === '' ? '0' : $digit;
}

// Kolom biaya DECIMAL(15,2) hanya muat sampai 13 digit sebelum koma
function nominalValid($digit) {
    return strlen($digit) <= 13;
}

// Bilangan bulat untuk jumlah dan stok, dibatasi agar tidak meluap
function bilangan($nilai, $maks = 999999) {
    $digit = angka($nilai);
    return strlen($digit) > strlen((string)$maks) ? $maks : min((int)$digit, $maks);
}

// Nilai DECIMAL dari database ("150000.00") menjadi digit polos ("150000") untuk atribut data-*
function digitUang($nilai) {
    return number_format((float)$nilai, 0, '', '');
}

function rupiah($nilai) {
    return 'Rp ' . number_format((float)$nilai, 0, ',', '.');
}

// 12500000 menjadi "12,5 jt" (untuk label grafik yang sempit)
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

// "2026-11-09" menjadi "9 Nov 2026"
function tanggalIndo($ymd) {
    if (!tanggalValid($ymd)) return '-';
    [$tahun, $bulan, $hari] = explode('-', $ymd);
    return (int)$hari . ' ' . BULAN_PENDEK[(int)$bulan - 1] . ' ' . $tahun;
}

// Tanggal dari form; jika kosong atau salah, pakai hari ini
function tanggalDariPost($kunci) {
    $tanggal = $_POST[$kunci] ?? '';
    return tanggalValid($tanggal) ? $tanggal : hariIni();
}

// ---------- Pesan sukses atau gagal setelah simpan ----------

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

// ---------- Warna label status ----------

function kelasStatus($status) {
    $s = strtolower(trim((string)$status));
    if ($s === 'selesai')    return 'st-selesai';
    if ($s === 'dikerjakan') return 'st-dikerjakan';
    return 'st-menunggu';
}

function kelasBayar($status) {
    return $status === 'Lunas' ? 'st-lunas' : 'st-belum';
}

// Mengembalikan [kelas CSS, teks] untuk kondisi stok
function kondisiStok($stok, $minimum) {
    if ($stok <= 0)        return ['st-habis', 'Habis'];
    if ($stok <= $minimum) return ['st-menipis', 'Menipis'];
    return ['st-aman', 'Aman'];
}

// ---------- ID otomatis berurutan ----------

// Contoh: idBerikutnya($conn, 'pelanggan', 'P') -> "P006" jika ID terbesar saat ini "P005".
// Hanya ID berpola awalan+angka yang dihitung, jadi data lama dengan ID bebas tetap aman.
function idBerikutnya($conn, $tabel, $awalan) {
    $hasil = mysqli_query($conn, "SELECT id FROM $tabel WHERE id LIKE '$awalan%'");
    $pola  = '/^' . preg_quote($awalan, '/') . '(\d+)$/i';
    $maks  = 0;
    while ($baris = mysqli_fetch_assoc($hasil)) {
        if (preg_match($pola, $baris['id'], $m)) $maks = max($maks, (int)$m[1]);
    }
    return $awalan . str_pad((string)($maks + 1), 3, '0', STR_PAD_LEFT);
}

// ---------- Pemeriksaan form servis dan restorasi ----------

// Mengembalikan pesan kesalahan, atau '' jika semua isian benar.
// Semua parameter sudah di-escape. $id_mekanik kosong berarti tanpa mekanik.
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

// Nilai SQL untuk kolom id_mekanik: NULL jika kosong
function sqlMekanik($id_mekanik) {
    return $id_mekanik === '' ? 'NULL' : "'$id_mekanik'";
}

// Nilai SQL untuk tanggal_selesai: terisi saat status Selesai (tanggal lama dipertahankan), kosong jika tidak
function sqlTanggalSelesai($status) {
    return $status === 'Selesai' ? "COALESCE(tanggal_selesai, '" . hariIni() . "')" : 'NULL';
}

// ---------- Biaya servis dan stok suku cadang ----------

// Biaya servis = biaya jasa + total suku cadang yang dipakai. $idServis harus sudah di-escape.
function hitungUlangBiayaServis($conn, $idServis) {
    mysqli_query($conn, "UPDATE servis SET biaya = biaya_jasa + (
        SELECT COALESCE(SUM(jumlah * harga_satuan), 0) FROM servis_item WHERE servis_item.id_servis = servis.id
    ) WHERE id = '$idServis'");
}

// Mengembalikan stok semua suku cadang yang dipakai satu servis. $idServis harus sudah di-escape.
function kembalikanStokServis($conn, $idServis) {
    mysqli_query($conn, "UPDATE sparepart SET stok = stok + (
        SELECT COALESCE(SUM(jumlah), 0) FROM servis_item
        WHERE servis_item.id_servis = '$idServis' AND servis_item.id_sparepart = sparepart.id
    ) WHERE id IN (SELECT id_sparepart FROM servis_item WHERE id_servis = '$idServis')");
}

// Pekerjaan yang dibatalkan (dihapus sebelum selesai) mengembalikan suku cadangnya ke rak.
// Servis berstatus Selesai tidak dikembalikan karena suku cadangnya sudah terpakai.
// $syarat adalah potongan WHERE yang sudah di-escape oleh pemanggil.
function kembalikanStokServisBelumSelesai($conn, $syarat) {
    $hasil = mysqli_query($conn, "SELECT id FROM servis WHERE status <> 'Selesai' AND ($syarat)");
    while ($baris = mysqli_fetch_assoc($hasil)) {
        kembalikanStokServis($conn, mysqli_real_escape_string($conn, $baris['id']));
    }
}

// ---------- Kode resi dan pelacakan servis ----------

// Kode resi unik, contoh "SRV-K7M2XQ". Huruf/angka yang mudah tertukar (O, 0, I, 1) tidak dipakai
// supaya mudah dibaca dari nota dan diketik pelanggan. 31^6 = lebih dari 887 juta kemungkinan.
function buatKodeResi($conn) {
    $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $kode = 'SRV-';
        for ($i = 0; $i < 6; $i++) {
            $kode .= $huruf[random_int(0, strlen($huruf) - 1)];
        }
        $ada = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM servis WHERE kode_resi = '$kode'"));
    } while ((int)$ada[0] > 0);
    return $kode;
}

// Servis lama (sebelum fitur Lacak ada) diberi kode resi satu kali saja
function isiKodeResiKosong($conn) {
    $hasil = mysqli_query($conn, "SELECT id FROM servis WHERE kode_resi IS NULL OR kode_resi = ''");
    $daftar = [];
    while ($baris = mysqli_fetch_assoc($hasil)) { $daftar[] = $baris['id']; }
    foreach ($daftar as $id) {
        $id   = mysqli_real_escape_string($conn, $id);
        $kode = buatKodeResi($conn);
        mysqli_query($conn, "UPDATE servis SET kode_resi = '$kode' WHERE id = '$id'");
    }
}

// "D 1234 GR" menjadi "D 12** GR". Halaman publik tidak boleh menampilkan nopol utuh.
function samarkanNopol($nopol) {
    $bagian = preg_split('/\s+/', trim((string)$nopol));
    if (count($bagian) >= 3) {
        $angka     = $bagian[1];
        $tampil    = strlen($angka) > 2 ? 2 : 1;      // "1234" menjadi "12**", "90" menjadi "9*"
        $bagian[1] = substr($angka, 0, $tampil) . str_repeat('*', strlen($angka) - $tampil);
        return implode(' ', $bagian);
    }
    $n = strlen($nopol);
    return $n <= 4 ? $nopol : substr($nopol, 0, 2) . str_repeat('*', $n - 4) . substr($nopol, -2);
}

// Alamat lengkap halaman lacak untuk kode tertentu, mis. http://localhost/bengkel-web/lacak.php?q=SRV-K7M2XQ
function urlLacak($kode = '') {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https://' : 'http://') . $host . $dir . '/lacak.php' . ($kode !== '' ? '?q=' . urlencode($kode) : '');
}

// ---------- Pemeriksaan versi database ----------

// Jika database masih versi lama (migrasi.sql / database.sql belum dijalankan), tampilkan
// petunjuk yang jelas, bukan "Fatal error: Unknown column ...". Hasilnya diingat selama sesi login.
function periksaSkema($conn) {
    $sesi = session_status() === PHP_SESSION_ACTIVE;
    if ($sesi && ($_SESSION['skema_ok'] ?? 0) === SKEMA_VERSI) return;

    $uji = [
        'tabel mekanik'     => 'SELECT id, nama, telp, spesialis FROM mekanik LIMIT 0',
        'tabel sparepart'   => 'SELECT id, nama, satuan, harga, stok, stok_minimum FROM sparepart LIMIT 0',
        'tabel servis_item' => 'SELECT id, id_servis, id_sparepart, jumlah, harga_satuan FROM servis_item LIMIT 0',
        'kolom baru pada tabel servis (id_mekanik, biaya_jasa, status_bayar, tanggal_masuk, tanggal_selesai)'
            => 'SELECT id_mekanik, biaya_jasa, status_bayar, tanggal_masuk, tanggal_selesai FROM servis LIMIT 0',
        'kolom baru pada tabel restorasi (id_mekanik, status_bayar, tanggal_masuk, tanggal_selesai)'
            => 'SELECT id_mekanik, status_bayar, tanggal_masuk, tanggal_selesai FROM restorasi LIMIT 0',
        'kolom kode_resi pada tabel servis (untuk fitur Lacak servis)'
            => 'SELECT kode_resi FROM servis LIMIT 0',
    ];
    $kurang = [];
    foreach ($uji as $nama => $sql) {
        try { $hasil = @mysqli_query($conn, $sql); } catch (Throwable $e) { $hasil = false; }
        if (!$hasil) $kurang[] = $nama;
    }
    if (!$kurang) {
        isiKodeResiKosong($conn);
        if ($sesi) $_SESSION['skema_ok'] = SKEMA_VERSI;
        return;
    }

    // Hanya kode_resi yang kurang: database sudah pernah dimigrasi, cukup migrasi_resi.sql
    $hanya_resi = count($kurang) === 1 && strpos($kurang[0], 'kode_resi') !== false;
    $berkas     = $hanya_resi ? 'migrasi_resi.sql' : 'migrasi.sql';

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
        <li>Klik tab <b>SQL</b>, tempel seluruh isi file <code><?= $berkas; ?></code>, lalu klik <b>Kirim</b>.</li>
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
