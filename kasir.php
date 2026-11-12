<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

const KASIR_MAKS_BARIS  = 50;
const KASIR_MAKS_JUMLAH = 9999;
const KASIR_MAKS_TOTAL  = 9999999999999;

function kasirTeks($nilai) {
    return is_scalar($nilai) ? (string)$nilai : '';
}

function kasirBulat($nilai) {
    $teks = trim(kasirTeks($nilai));
    return preg_match('/^\d{1,4}$/', $teks) ? (int)$teks : 0;
}

function kasirBaris($daftar, $pilih = '', $jumlah = '1') {
    ob_start();
    ?>
    <div class="kasir-baris" data-baris>
        <div class="kasir-pilih-wrap">
            <select name="id_sparepart[]" class="form-select" data-pilih aria-label="Sparepart">
                <option value="">-- Pilih sparepart --</option>
                <?php foreach ($daftar as $p): ?>
                    <?php $habis = (int)$p['stok'] <= 0; ?>
                    <option value="<?= h($p['id']); ?>"
                        data-harga="<?= digitUang($p['harga']); ?>"
                        data-stok="<?= (int)$p['stok']; ?>"
                        data-satuan="<?= h($p['satuan']); ?>"
                        <?= $habis ? 'disabled' : ''; ?>
                        <?= (string)$p['id'] === (string)$pilih ? 'selected' : ''; ?>>
                        <?= h($p['nama']); ?> - <?= rupiah($p['harga']); ?> (<?= $habis ? 'habis' : 'stok ' . (int)$p['stok']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="kasir-info" data-info>Pilih sparepart untuk melihat harga dan stok.</div>
        </div>
        <input type="number" name="jumlah[]" class="form-control kasir-jumlah" min="1" max="<?= KASIR_MAKS_JUMLAH; ?>" step="1"
            value="<?= h($jumlah); ?>" inputmode="numeric" data-jumlah aria-label="Jumlah">
        <div class="kasir-sub" data-sub>Rp 0</div>
        <button type="button" class="btn btn-danger btn-sm kasir-hapus" data-hapus aria-label="Hapus baris">&times;</button>
    </div>
    <?php
    return ob_get_clean();
}

function kembalikanStokPenjualan($conn, $idPenjualan) {
    mysqli_query($conn, "UPDATE sparepart SET stok = stok + (
        SELECT COALESCE(SUM(jumlah), 0) FROM penjualan_item
        WHERE penjualan_item.id_penjualan = '$idPenjualan' AND penjualan_item.id_sparepart = sparepart.id
    ) WHERE id IN (SELECT id_sparepart FROM penjualan_item WHERE id_penjualan = '$idPenjualan')");
}

function kasirBatalkanTransaksi($conn) {
    try { mysqli_rollback($conn); } catch (Throwable $e) {
    }
}

if (isset($_POST['batal'])) {
    $id_mentah = potong(kasirTeks($_POST['id_batal'] ?? ''), 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    $ada       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM penjualan WHERE id = '$id'"));

    if (!$ada) {
        flash('Data penjualan tidak ditemukan.', 'danger');
    } else {
        try {
            mysqli_begin_transaction($conn);
            kembalikanStokPenjualan($conn, $id);
            mysqli_query($conn, "DELETE FROM penjualan WHERE id = '$id'");
            mysqli_commit($conn);
            flash("Penjualan $id_mentah dibatalkan. Stok sparepart sudah dikembalikan.");
        } catch (Throwable $e) {
            kasirBatalkanTransaksi($conn);
            flash('Penjualan gagal dibatalkan. Tidak ada data yang berubah.', 'danger');
        }
    }
    pindah('kasir.php');
}

$galat      = '';
$baris_form = [['', '1']];
$nama_form  = '';
$bayar_form = '';

if (isset($_POST['simpan'])) {
    $ids_post   = is_array($_POST['id_sparepart'] ?? null) ? $_POST['id_sparepart'] : [];
    $jml_post   = is_array($_POST['jumlah'] ?? null) ? $_POST['jumlah'] : [];
    $nama_form  = potong(kasirTeks($_POST['nama_pembeli'] ?? ''), 100);
    $bayar_form = trim(kasirTeks($_POST['bayar'] ?? ''));

    $baris_form   = [];
    $minta        = [];
    $jumlah_salah = false;
    foreach (array_slice($ids_post, 0, KASIR_MAKS_BARIS, true) as $i => $kode) {
        $kode = potong(kasirTeks($kode), 50);
        if ($kode === '') {
            continue;
        }
        $jml          = kasirTeks($jml_post[$i] ?? '1');
        $baris_form[] = [$kode, $jml];
        $qty          = kasirBulat($jml);
        if ($qty < 1) {
            $jumlah_salah = true;
            continue;
        }
        $minta[$kode] = ($minta[$kode] ?? 0) + $qty;
    }
    if (!$baris_form) {
        $baris_form = [['', '1']];
    }

    $data  = [];
    $total = 0;
    $bayar = 0;

    if ($jumlah_salah) {
        $galat = 'Jumlah harus berupa bilangan bulat 1 sampai ' . KASIR_MAKS_JUMLAH . '.';
    } elseif (!$minta) {
        $galat = 'Pilih minimal satu sparepart.';
    } else {
        $daftar_in = "'" . implode("','", array_map(function ($k) use ($conn) {
            return mysqli_real_escape_string($conn, (string)$k);
        }, array_keys($minta))) . "'";
        $hasil = mysqli_query($conn, "SELECT id, nama, harga, stok FROM sparepart WHERE id IN ($daftar_in)");
        while ($r = mysqli_fetch_assoc($hasil)) {
            $data[$r['id']] = $r;
        }

        $kurang = [];
        foreach ($minta as $kode => $qty) {
            if (!isset($data[$kode])) {
                $galat = 'Ada sparepart yang tidak ditemukan. Pilih dari daftar.';
                break;
            }
            if ((int)$data[$kode]['stok'] < $qty) {
                $kurang[] = $data[$kode]['nama'] . ' (sisa ' . (int)$data[$kode]['stok'] . ')';
            }
            $total += (float)digitUang($data[$kode]['harga']) * $qty;
        }

        if ($galat === '' && $kurang) {
            $galat = 'Stok tidak cukup: ' . implode(', ', $kurang) . '.';
        }
        if ($galat === '' && $total > KASIR_MAKS_TOTAL) {
            $galat = 'Total penjualan terlalu besar.';
        }

        $bayar = $total;
        if ($galat === '' && $bayar_form !== '') {
            $digit = angka($bayar_form);
            if (!nominalValid($digit)) {
                $galat = 'Uang diterima terlalu besar.';
            } elseif ((float)$digit < $total) {
                $galat = 'Uang diterima kurang ' . rupiah($total - (float)$digit) . '.';
            } else {
                $bayar = (float)$digit;
            }
        }
    }

    if ($galat === '') {
        $id_baru    = idBerikutnya($conn, 'penjualan', 'PJ');
        $nama_sql   = mysqli_real_escape_string($conn, $nama_form);
        $nama_nilai = $nama_sql === '' ? 'NULL' : "'$nama_sql'";
        $tanggal    = hariIni();
        $nilai_tot  = digitUang($total);
        $nilai_byr  = digitUang($bayar);
        $nilai_kmb  = digitUang($bayar - $total);

        try {
            mysqli_begin_transaction($conn);
            foreach ($minta as $kode => $qty) {
                $sp = mysqli_real_escape_string($conn, (string)$kode);
                mysqli_query($conn, "UPDATE sparepart SET stok = stok - $qty WHERE id = '$sp' AND stok >= $qty");
                if (mysqli_affected_rows($conn) < 1) {
                    throw new RuntimeException('stok');
                }
            }
            mysqli_query($conn, "INSERT INTO penjualan (id, tanggal, nama_pembeli, total, bayar, kembalian)
                VALUES ('$id_baru', '$tanggal', $nama_nilai, '$nilai_tot', '$nilai_byr', '$nilai_kmb')");
            foreach ($minta as $kode => $qty) {
                $sp    = mysqli_real_escape_string($conn, (string)$kode);
                $harga = digitUang($data[$kode]['harga']);
                mysqli_query($conn, "INSERT INTO penjualan_item (id_penjualan, id_sparepart, jumlah, harga_satuan)
                    VALUES ('$id_baru', '$sp', $qty, '$harga')");
            }
            mysqli_commit($conn);
            $_SESSION['penjualan_baru'] = ['id' => $id_baru, 'total' => $total];
            pindah('kasir.php');
        } catch (Throwable $e) {
            kasirBatalkanTransaksi($conn);
            $galat = $e->getMessage() === 'stok'
                ? 'Stok berubah saat diproses. Muat ulang halaman lalu coba lagi. Tidak ada stok yang berkurang.'
                : 'Penjualan gagal disimpan. Tidak ada stok yang berkurang.';
        }
    }
}

$baru = $_SESSION['penjualan_baru'] ?? null;
unset($_SESSION['penjualan_baru']);

$sparepart = [];
$hasil     = mysqli_query($conn, "SELECT id, nama, satuan, harga, stok FROM sparepart ORDER BY nama ASC");
while ($r = mysqli_fetch_assoc($hasil)) {
    $sparepart[] = $r;
}

$hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS jumlah, COALESCE(SUM(total), 0) AS total
    FROM penjualan WHERE tanggal = '" . hariIni() . "'"));

$riwayat = [];
$hasil   = mysqli_query($conn, "SELECT id, tanggal, nama_pembeli, total FROM penjualan
    ORDER BY tanggal DESC, LENGTH(id) DESC, id DESC LIMIT 15");
while ($r = mysqli_fetch_assoc($hasil)) {
    $r['barang']       = [];
    $riwayat[$r['id']] = $r;
}
if ($riwayat) {
    $daftar_id = "'" . implode("','", array_map(function ($k) use ($conn) {
        return mysqli_real_escape_string($conn, (string)$k);
    }, array_keys($riwayat))) . "'";
    $hasil = mysqli_query($conn, "SELECT penjualan_item.id_penjualan, penjualan_item.jumlah, sparepart.nama
        FROM penjualan_item JOIN sparepart ON penjualan_item.id_sparepart = sparepart.id
        WHERE penjualan_item.id_penjualan IN ($daftar_id) ORDER BY penjualan_item.id ASC");
    while ($r = mysqli_fetch_assoc($hasil)) {
        $riwayat[$r['id_penjualan']]['barang'][] = $r['nama'] . ' x' . (int)$r['jumlah'];
    }
}

$judul_halaman = 'Kasir Penjualan';
$menu_aktif    = 'kasir';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Kasir penjualan</h1>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="gr-total">
                <span>Penjualan hari ini</span>
                <strong><?= rupiah($hari_ini['total']); ?></strong>
            </div>
            <div class="gr-total">
                <span>Transaksi hari ini</span>
                <strong><?= (int)$hari_ini['jumlah']; ?></strong>
            </div>
        </div>
    </div>

    <?= tampilFlash(); ?>

    <?php if ($baru): ?>
        <div class="alert alert-success kasir-sukses" role="alert">
            <span>Penjualan <strong><?= h($baru['id']); ?></strong> tersimpan. Total <?= rupiah($baru['total']); ?>. Stok sparepart sudah dikurangi.</span>
            <a class="btn btn-secondary btn-sm" href="nota.php?jenis=penjualan&amp;id=<?= urlencode($baru['id']); ?>" target="_blank" rel="noopener">Cetak nota</a>
        </div>
    <?php endif; ?>

    <?php if ($galat !== ''): ?>
        <div class="alert alert-danger py-2" role="alert"><?= h($galat); ?></div>
    <?php endif; ?>

    <?php if (!$sparepart): ?>
        <div class="gr-panel gr-panel-pad">
            <p class="mb-2">Belum ada sparepart yang bisa dijual.</p>
            <a href="sparepart.php" class="btn btn-primary btn-sm">Tambah sparepart</a>
        </div>
    <?php else: ?>
        <form method="post" id="formKasir" class="row g-4" autocomplete="off">
            <input type="hidden" name="simpan" value="1">

            <div class="col-lg-8">
                <div class="gr-panel gr-panel-pad">
                    <h2 class="gr-h2">Keranjang</h2>
                    <div id="kasirBaris" class="kasir-daftar">
                        <?php foreach ($baris_form as [$pilih, $jumlah]): ?>
                            <?= kasirBaris($sparepart, $pilih, $jumlah); ?>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="tambahBaris" class="btn btn-secondary btn-sm mt-3">Tambah baris</button>
                    <template id="templatBaris"><?= kasirBaris($sparepart); ?></template>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="gr-panel gr-panel-pad kasir-ringkasan">
                    <h2 class="gr-h2">Pembayaran</h2>

                    <label class="form-label" for="namaPembeli">Nama pembeli (opsional)</label>
                    <input type="text" id="namaPembeli" name="nama_pembeli" class="form-control" maxlength="100"
                        value="<?= h($nama_form); ?>" placeholder="Pembeli umum">

                    <div class="kasir-total">
                        <span>Total</span>
                        <strong id="kasirTotal">Rp 0</strong>
                    </div>

                    <label class="form-label" for="uangDiterima">Uang diterima (opsional)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="uangDiterima" name="bayar" class="form-control" data-rupiah inputmode="numeric"
                            value="<?= h($bayar_form); ?>" placeholder="Kosong = uang pas">
                        <button type="button" id="uangPas" class="btn btn-secondary">Uang pas</button>
                    </div>
                    <div id="kasirKembali" class="kasir-kembali" aria-live="polite"></div>

                    <button type="submit" id="tombolSimpan" class="btn btn-primary w-100 mt-3">Simpan penjualan</button>
                    <p class="kasir-catatan">Stok sparepart berkurang otomatis saat penjualan disimpan.</p>
                </div>
            </div>
        </form>
    <?php endif; ?>

    <h2 class="gr-h2 mt-5">Penjualan terbaru</h2>
    <div class="gr-panel">
        <div class="table-responsive">
            <table class="table table-gr table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pembeli</th>
                        <th>Barang</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($riwayat): ?>
                        <?php foreach ($riwayat as $p): ?>
                            <tr>
                                <td class="kol-id"><?= h($p['id']); ?></td>
                                <td class="kol-tgl"><?= tanggalIndo($p['tanggal']); ?></td>
                                <td><?= $p['nama_pembeli'] !== null && $p['nama_pembeli'] !== '' ? h($p['nama_pembeli']) : '<span class="text-secondary">Umum</span>'; ?></td>
                                <td class="kol-uraian"><?= h(implode(', ', $p['barang'])); ?></td>
                                <td><strong><?= rupiah($p['total']); ?></strong></td>
                                <td class="kol-aksi">
                                    <a class="btn btn-secondary btn-sm" href="nota.php?jenis=penjualan&amp;id=<?= urlencode($p['id']); ?>" target="_blank" rel="noopener">Nota</a>
                                    <form method="post" class="d-inline" data-konfirmasi="Batalkan penjualan ini? Stok sparepart akan dikembalikan.">
                                        <input type="hidden" name="id_batal" value="<?= h($p['id']); ?>">
                                        <button type="submit" name="batal" class="btn btn-danger btn-sm">Batalkan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="gr-empty">Belum ada penjualan. Transaksi pertama akan muncul di sini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($sparepart): ?>
    <script src="assets/js/kasir.js"></script>
<?php endif; ?>
<?php include 'partials/footer.php'; ?>
