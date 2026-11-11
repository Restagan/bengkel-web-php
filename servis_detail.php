<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

$id_mentah = potong($_GET['id'] ?? '', 50);
$id        = mysqli_real_escape_string($conn, $id_mentah);
$alamat    = 'servis_detail.php?id=' . urlencode($id_mentah);

$ambil = "SELECT servis.*, kendaraan.merk, kendaraan.nopol, kendaraan.tahun,
        pelanggan.id AS id_pelanggan, pelanggan.nama AS nama_pelanggan, pelanggan.telp AS telp_pelanggan,
        mekanik.nama AS nama_mekanik
    FROM servis
    JOIN kendaraan ON servis.id_kendaraan = kendaraan.id
    JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
    LEFT JOIN mekanik ON servis.id_mekanik = mekanik.id
    WHERE servis.id = '$id'";
$servis = mysqli_fetch_assoc(mysqli_query($conn, $ambil));

if (!$servis) {
    flash('Data servis tidak ditemukan.', 'danger');
    pindah('servis.php');
}

if (isset($_POST['tambah_item'])) {
    $id_sparepart = dariPost($conn, 'id_sparepart', 50);
    $jumlah       = bilangan($_POST['jumlah'] ?? '', 9999);

    $part = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, nama, harga, stok FROM sparepart WHERE id = '$id_sparepart'"));
    if (!$part) {
        flash('Suku cadang tidak ditemukan. Pilih dari daftar.', 'danger');
    } elseif ($jumlah < 1) {
        flash('Jumlah minimal 1.', 'danger');
    } else {
        // Pengurangan stok bersyarat: hanya berhasil jika stok masih cukup (aman bila dua orang menambah bersamaan)
        mysqli_query($conn, "UPDATE sparepart SET stok = stok - $jumlah WHERE id = '$id_sparepart' AND stok >= $jumlah");
        if (mysqli_affected_rows($conn) < 1) {
            $sisa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stok FROM sparepart WHERE id = '$id_sparepart'"));
            flash('Stok ' . $part['nama'] . ' tidak cukup. Sisa stok: ' . (int)$sisa['stok'] . '.', 'danger');
        } else {
            $harga = digitUang($part['harga']);
            mysqli_query($conn, "INSERT INTO servis_item (id_servis, id_sparepart, jumlah, harga_satuan)
                VALUES ('$id', '$id_sparepart', '$jumlah', '$harga')");
            hitungUlangBiayaServis($conn, $id);
            flash($jumlah . ' ' . $part['nama'] . ' ditambahkan ke servis. Stok sudah dikurangi.');
        }
    }
    pindah($alamat);
}

// ---------- Hapus suku cadang (stok dikembalikan) ----------
if (isset($_POST['hapus_item'])) {
    $id_item = (int)($_POST['id_item'] ?? 0);
    $item    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, id_sparepart, jumlah FROM servis_item
        WHERE id = $id_item AND id_servis = '$id'"));
    if (!$item) {
        flash('Suku cadang tidak ditemukan pada servis ini.', 'danger');
    } else {
        $sp = mysqli_real_escape_string($conn, $item['id_sparepart']);
        mysqli_query($conn, "UPDATE sparepart SET stok = stok + " . (int)$item['jumlah'] . " WHERE id = '$sp'");
        mysqli_query($conn, "DELETE FROM servis_item WHERE id = $id_item");
        hitungUlangBiayaServis($conn, $id);
        flash('Suku cadang dihapus dari servis. Stok sudah dikembalikan.');
    }
    pindah($alamat);
}

// ---------- Data tampilan ----------
$items = [];
$hasil = mysqli_query($conn, "SELECT servis_item.*, sparepart.nama, sparepart.satuan
    FROM servis_item JOIN sparepart ON servis_item.id_sparepart = sparepart.id
    WHERE servis_item.id_servis = '$id' ORDER BY servis_item.id ASC");
while ($r = mysqli_fetch_assoc($hasil)) { $items[] = $r; }

$total_part = 0;
foreach ($items as $it) { $total_part += $it['jumlah'] * $it['harga_satuan']; }

$daftar_part = [];
$hasil = mysqli_query($conn, "SELECT id, nama, satuan, harga, stok FROM sparepart WHERE stok > 0 ORDER BY nama ASC");
while ($r = mysqli_fetch_assoc($hasil)) { $daftar_part[] = $r; }

$judul_halaman = 'Detail Servis ' . $servis['id'];
$menu_aktif    = 'servis';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="servis.php" class="gr-back">&larr; Kembali ke data servis</a>
            <h1 class="gr-title mt-1">Servis <?= h($servis['id']); ?></h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="nota.php?jenis=servis&amp;id=<?= urlencode($servis['id']); ?>" target="_blank" rel="noopener" class="btn btn-primary">Cetak nota</a>
        </div>
    </div>

    <?= tampilFlash(); ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="gr-panel gr-panel-pad h-100">
                <h2 class="gr-h2">Informasi servis</h2>
                <dl class="gr-dl">
                    <dt>Kendaraan</dt>
                    <dd><?= h($servis['merk']); ?> (<?= h($servis['tahun']); ?>)<br><span class="kol-sub"><?= h($servis['nopol']); ?></span></dd>
                    <dt>Pemilik</dt>
                    <dd><?= h($servis['nama_pelanggan']); ?><br><span class="kol-sub"><?= h($servis['telp_pelanggan']); ?></span></dd>
                    <dt>Mekanik</dt>
                    <dd><?= $servis['nama_mekanik'] ? h($servis['nama_mekanik']) : '<span class="text-secondary">Belum ditentukan</span>'; ?></dd>
                    <dt>Keluhan</dt>
                    <dd><?= h($servis['keluhan']); ?></dd>
                    <dt>Tanggal masuk</dt>
                    <dd><?= tanggalIndo($servis['tanggal_masuk']); ?></dd>
                    <dt>Tanggal selesai</dt>
                    <dd><?= $servis['tanggal_selesai'] ? tanggalIndo($servis['tanggal_selesai']) : '<span class="text-secondary">Belum selesai</span>'; ?></dd>
                    <dt>Status</dt>
                    <dd>
                        <span class="st <?= kelasStatus($servis['status']); ?>"><?= h($servis['status']); ?></span>
                        <span class="st <?= kelasBayar($servis['status_bayar']); ?>"><?= h($servis['status_bayar']); ?></span>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="gr-panel gr-panel-pad h-100">
                <h2 class="gr-h2">Suku cadang yang dipakai</h2>

                <?php if ($daftar_part): ?>
                    <form method="post" class="row g-2 align-items-end mb-3">
                        <div class="col-sm-7">
                            <label class="form-label" for="pilihPart">Suku cadang</label>
                            <select name="id_sparepart" id="pilihPart" class="form-select" required data-batas-jumlah="#isiJumlah">
                                <option value="" disabled selected>-- Pilih suku cadang --</option>
                                <?php foreach ($daftar_part as $p): ?>
                                    <option value="<?= h($p['id']); ?>" data-stok="<?= (int)$p['stok']; ?>">
                                        <?= h($p['nama']); ?> - <?= rupiah($p['harga']); ?> (stok <?= (int)$p['stok']; ?> <?= h($p['satuan']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-sm-2">
                            <label class="form-label" for="isiJumlah">Jumlah</label>
                            <input type="number" name="jumlah" id="isiJumlah" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-6 col-sm-3">
                            <button type="submit" name="tambah_item" class="btn btn-primary w-100">Tambah</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning py-2" role="alert">
                        Belum ada suku cadang dengan stok tersedia. Tambahkan atau isi stok di menu <a href="sparepart.php">Sparepart</a>.
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-gr table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Suku cadang</th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items): ?>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td>
                                            <?= h($it['nama']); ?>
                                            <div class="kol-sub"><?= (int)$it['jumlah']; ?> <?= h($it['satuan']); ?> &times; <?= rupiah($it['harga_satuan']); ?></div>
                                        </td>
                                        <td class="text-end kol-rp"><?= rupiah($it['jumlah'] * $it['harga_satuan']); ?></td>
                                        <td class="kol-hapus">
                                            <form method="post" class="d-inline" data-konfirmasi="Hapus suku cadang ini dari servis? Stoknya dikembalikan.">
                                                <input type="hidden" name="id_item" value="<?= (int)$it['id']; ?>">
                                                <button type="submit" name="hapus_item" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="gr-empty">Belum ada suku cadang yang dicatat untuk servis ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-end">Biaya jasa</td>
                                <td class="text-end kol-rp"><?= rupiah($servis['biaya_jasa']); ?></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="text-end">Total suku cadang</td>
                                <td class="text-end kol-rp"><?= rupiah($total_part); ?></td>
                                <td></td>
                            </tr>
                            <tr class="baris-total">
                                <td class="text-end">Total biaya</td>
                                <td class="text-end kol-rp"><?= rupiah($servis['biaya']); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="form-text mt-3 mb-0">Menambah suku cadang mengurangi stok, menghapusnya mengembalikan stok. Biaya jasa diubah lewat tombol Edit di halaman Data servis.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
