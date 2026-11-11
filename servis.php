<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

// ---------- Tambah (ID dibuat otomatis: S001, S002, ...) ----------
// Biaya yang diisi adalah biaya jasa. Suku cadang ditambahkan lewat halaman Detail.
if (isset($_POST['tambah'])) {
    $id_kendaraan = dariPost($conn, 'id_kendaraan', 50);
    $id_mekanik   = dariPost($conn, 'id_mekanik', 50);
    $keluhan      = dariPost($conn, 'keluhan', 500);
    $biaya        = angka($_POST['biaya_jasa'] ?? '');
    $status       = pilihan($_POST['status'] ?? '', STATUS_KERJA, 'Menunggu');
    $bayar        = pilihan($_POST['status_bayar'] ?? '', STATUS_BAYAR, 'Belum Lunas');
    $masuk        = tanggalDariPost('tanggal_masuk');

    $salah = periksaPekerjaan($conn, $id_kendaraan, $id_mekanik, $keluhan, $biaya, 'keluhan');
    if ($salah !== '') {
        flash($salah, 'danger');
    } else {
        $id      = idBerikutnya($conn, 'servis', 'S');
        $resi    = buatKodeResi($conn);   // kode unik untuk halaman Lacak Servis (lacak.php)
        $selesai = $status === 'Selesai' ? "'" . hariIni() . "'" : 'NULL';
        mysqli_query($conn, "INSERT INTO servis
            (id, kode_resi, id_kendaraan, id_mekanik, keluhan, status, biaya_jasa, biaya, status_bayar, tanggal_masuk, tanggal_selesai)
            VALUES ('$id', '$resi', '$id_kendaraan', " . sqlMekanik($id_mekanik) . ", '$keluhan', '$status', '$biaya', '$biaya', '$bayar', '$masuk', $selesai)");
        flash("Servis $id berhasil ditambahkan dengan kode resi $resi. Buka Detail untuk mencatat suku cadang yang dipakai.");
    }
    pindah('servis.php');
}

// ---------- Ubah ----------
if (isset($_POST['ubah'])) {
    $id_mentah    = potong($_POST['id'] ?? '', 50);
    $id           = mysqli_real_escape_string($conn, $id_mentah);
    $id_kendaraan = dariPost($conn, 'id_kendaraan', 50);
    $id_mekanik   = dariPost($conn, 'id_mekanik', 50);
    $keluhan      = dariPost($conn, 'keluhan', 500);
    $biaya        = angka($_POST['biaya_jasa'] ?? '');
    $status       = pilihan($_POST['status'] ?? '', STATUS_KERJA, 'Menunggu');
    $bayar        = pilihan($_POST['status_bayar'] ?? '', STATUS_BAYAR, 'Belum Lunas');
    $masuk        = tanggalDariPost('tanggal_masuk');

    $salah = periksaPekerjaan($conn, $id_kendaraan, $id_mekanik, $keluhan, $biaya, 'keluhan');
    if ($salah !== '') {
        flash($salah, 'danger');
    } else {
        mysqli_query($conn, "UPDATE servis SET id_kendaraan = '$id_kendaraan', id_mekanik = " . sqlMekanik($id_mekanik) . ",
            keluhan = '$keluhan', status = '$status', status_bayar = '$bayar', biaya_jasa = '$biaya',
            tanggal_masuk = '$masuk', tanggal_selesai = " . sqlTanggalSelesai($status) . " WHERE id = '$id'");
        hitungUlangBiayaServis($conn, $id);
        flash("Data servis $id_mentah berhasil diperbarui.");
    }
    pindah('servis.php');
}

// ---------- Ubah status pekerjaan dan pembayaran (jalan pintas) ----------
if (isset($_POST['ubah_status'])) {
    $id_mentah = potong($_POST['id'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    $status    = pilihan($_POST['status'] ?? '', STATUS_KERJA, 'Menunggu');
    $bayar     = pilihan($_POST['status_bayar'] ?? '', STATUS_BAYAR, 'Belum Lunas');

    mysqli_query($conn, "UPDATE servis SET status = '$status', status_bayar = '$bayar',
        tanggal_selesai = " . sqlTanggalSelesai($status) . " WHERE id = '$id'");
    flash("Servis $id_mentah: status $status, pembayaran $bayar.");
    pindah('servis.php');
}

// ---------- Hapus ----------
if (isset($_POST['hapus'])) {
    $id_mentah = potong($_POST['id_hapus'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);

    // Servis yang belum selesai dianggap batal, jadi suku cadangnya dikembalikan ke stok
    kembalikanStokServisBelumSelesai($conn, "id = '$id'");
    mysqli_query($conn, "DELETE FROM servis WHERE id = '$id'");

    if (mysqli_affected_rows($conn) > 0) {
        flash("Servis $id_mentah telah dihapus.");
    } else {
        flash('Data servis tidak ditemukan.', 'danger');
    }
    pindah('servis.php');
}

// ---------- Daftar dan filter ----------
$keyword  = potong($_GET['cari'] ?? '', 100);
$f_status = pilihan($_GET['status'] ?? '', array_merge([''], STATUS_KERJA), '');
$f_bayar  = pilihan($_GET['bayar'] ?? '', array_merge([''], STATUS_BAYAR), '');
$urut     = pilihan($_GET['urut'] ?? 'id', ['id', 'terbaru', 'murah', 'mahal'], 'id');
$kw       = mysqli_real_escape_string($conn, $keyword);

$urut_sql = [
    'id'     => 'LENGTH(servis.id) ASC, servis.id ASC',
    'terbaru' => 'servis.tanggal_masuk DESC, LENGTH(servis.id) DESC, servis.id DESC',
    'murah'  => 'servis.biaya ASC',
    'mahal'  => 'servis.biaya DESC',
];
$syarat  = $f_status !== '' ? "AND servis.status = '$f_status'" : '';
$syarat .= $f_bayar !== '' ? " AND servis.status_bayar = '$f_bayar'" : '';

$result = mysqli_query($conn, "SELECT servis.*, kendaraan.merk, kendaraan.nopol,
        pelanggan.nama AS nama_pelanggan, mekanik.nama AS nama_mekanik
    FROM servis
    JOIN kendaraan ON servis.id_kendaraan = kendaraan.id
    JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
    LEFT JOIN mekanik ON servis.id_mekanik = mekanik.id
    WHERE (servis.id LIKE '%$kw%' OR servis.kode_resi LIKE '%$kw%' OR kendaraan.nopol LIKE '%$kw%' OR kendaraan.merk LIKE '%$kw%'
        OR pelanggan.nama LIKE '%$kw%' OR servis.keluhan LIKE '%$kw%') $syarat
    ORDER BY {$urut_sql[$urut]}");

$ringkas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(biaya), 0) AS total,
    COALESCE(SUM(CASE WHEN status_bayar <> 'Lunas' THEN biaya ELSE 0 END), 0) AS belum FROM servis"));

// Pilihan untuk form
$daftar_kendaraan = [];
$hasil = mysqli_query($conn, "SELECT kendaraan.id, kendaraan.merk, kendaraan.nopol, pelanggan.nama AS pemilik
    FROM kendaraan JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id ORDER BY kendaraan.nopol ASC");
while ($k = mysqli_fetch_assoc($hasil)) { $daftar_kendaraan[] = $k; }

$daftar_mekanik = [];
$hasil = mysqli_query($conn, "SELECT id, nama FROM mekanik ORDER BY nama ASC");
while ($m = mysqli_fetch_assoc($hasil)) { $daftar_mekanik[] = $m; }

$id_berikut = idBerikutnya($conn, 'servis', 'S');

$judul_halaman = 'Data Servis';
$menu_aktif    = 'servis';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Data servis</h1>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="gr-total">
                <span>Total pendapatan servis</span>
                <strong><?= rupiah($ringkas['total']); ?></strong>
            </div>
            <div class="gr-total">
                <span>Belum dibayar</span>
                <strong><?= rupiah($ringkas['belum']); ?></strong>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah servis</button>
        </div>
    </div>

    <?= tampilFlash(); ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-lg-3 col-md-6">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID, kode resi, nopol..." aria-label="Cari servis" value="<?= h($keyword); ?>">
            </div>
            <div class="col-lg-2 col-md-6">
                <select name="status" class="form-select" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <?php foreach (STATUS_KERJA as $s): ?>
                        <option value="<?= $s; ?>" <?= $f_status === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <select name="bayar" class="form-select" aria-label="Filter pembayaran">
                    <option value="">Semua bayar</option>
                    <?php foreach (STATUS_BAYAR as $s): ?>
                        <option value="<?= $s; ?>" <?= $f_bayar === $s ? 'selected' : ''; ?>><?= $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <select name="urut" class="form-select" aria-label="Urutan">
                    <option value="id" <?= $urut === 'id' ? 'selected' : ''; ?>>Urutkan: ID servis</option>
                    <option value="terbaru" <?= $urut === 'terbaru' ? 'selected' : ''; ?>>Urutkan: tanggal terbaru</option>
                    <option value="murah" <?= $urut === 'murah' ? 'selected' : ''; ?>>Biaya: termurah ke termahal</option>
                    <option value="mahal" <?= $urut === 'mahal' ? 'selected' : ''; ?>>Biaya: termahal ke termurah</option>
                </select>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="gr-panel">
        <div class="table-responsive">
            <table class="table table-gr table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID servis</th>
                        <th>Kendaraan</th>
                        <th>Keluhan</th>
                        <th>Tanggal</th>
                        <th>Biaya</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php $biaya_part = $row['biaya'] - $row['biaya_jasa']; ?>
                            <tr>
                                <td class="kol-id"><?= h($row['id']); ?>
                                    <?php if ($row['kode_resi']): ?><div class="kol-sub"><?= h($row['kode_resi']); ?></div><?php endif; ?>
                                </td>
                                <td class="kol-kendaraan">
                                    <?= h($row['merk']); ?>
                                    <div class="kol-sub"><?= h($row['nopol']); ?> - <?= h($row['nama_pelanggan']); ?></div>
                                    <div class="kol-sub"><?= $row['nama_mekanik'] ? 'Mekanik: ' . h($row['nama_mekanik']) : 'Belum ada mekanik'; ?></div>
                                </td>
                                <td class="kol-uraian"><?= h($row['keluhan']); ?></td>
                                <td class="kol-tgl">
                                    <?= tanggalIndo($row['tanggal_masuk']); ?>
                                    <?php if ($row['tanggal_selesai']): ?>
                                        <div class="kol-sub">Selesai <?= tanggalIndo($row['tanggal_selesai']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="kol-rp">
                                    <?= rupiah($row['biaya']); ?>
                                    <?php if ($biaya_part > 0): ?>
                                        <div class="kol-sub">Jasa <?= rupiah($row['biaya_jasa']); ?> + suku cadang <?= rupiah($biaya_part); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="st <?= kelasStatus($row['status']); ?>"><?= h($row['status']); ?></span>
                                        <span class="st <?= kelasBayar($row['status_bayar']); ?>"><?= h($row['status_bayar']); ?></span>
                                    </div>
                                </td>
                                <td class="kol-aksi">
                                    <a href="servis_detail.php?id=<?= urlencode($row['id']); ?>" class="btn btn-secondary btn-sm">Detail</a>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalStatus"
                                        data-id="<?= h($row['id']); ?>"
                                        data-status="<?= h($row['status']); ?>"
                                        data-status_bayar="<?= h($row['status_bayar']); ?>">Status</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="<?= h($row['id']); ?>"
                                        data-id_kendaraan="<?= h($row['id_kendaraan']); ?>"
                                        data-id_mekanik="<?= h($row['id_mekanik']); ?>"
                                        data-keluhan="<?= h($row['keluhan']); ?>"
                                        data-biaya_jasa="<?= digitUang($row['biaya_jasa']); ?>"
                                        data-status="<?= h($row['status']); ?>"
                                        data-status_bayar="<?= h($row['status_bayar']); ?>"
                                        data-tanggal_masuk="<?= h($row['tanggal_masuk']); ?>">Edit</button>
                                    <a href="nota.php?jenis=servis&amp;id=<?= urlencode($row['id']); ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Nota</a>
                                    <form method="post" class="d-inline" data-konfirmasi="Hapus data servis ini? Suku cadang yang dipakai dikembalikan ke stok jika servis belum selesai.">
                                        <input type="hidden" name="id_hapus" value="<?= h($row['id']); ?>">
                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="gr-empty">
                                <?php if ($keyword !== '' || $f_status !== '' || $f_bayar !== ''): ?>
                                    Tidak ada servis yang cocok dengan filter ini. Ubah kata kunci atau filter.
                                <?php else: ?>
                                    Belum ada data servis. Klik <strong>Tambah servis</strong> untuk mencatat yang pertama.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="judulModalTambah" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalTambah">Tambah data servis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">ID servis</label>
                            <input type="text" class="form-control" value="<?= h($id_berikut); ?>" readonly>
                            <div class="form-text">Dibuat otomatis.</div>
                        </div>
                        <div class="col-sm-8">
                            <label class="form-label">Kendaraan</label>
                            <select name="id_kendaraan" class="form-select" required>
                                <option value="" disabled selected><?= $daftar_kendaraan ? '-- Pilih kendaraan --' : 'Belum ada kendaraan, tambahkan dulu'; ?></option>
                                <?php foreach ($daftar_kendaraan as $k): ?>
                                    <option value="<?= h($k['id']); ?>"><?= h($k['nopol']); ?> - <?= h($k['merk']); ?> (<?= h($k['pemilik']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keluhan</label>
                            <textarea name="keluhan" class="form-control" rows="2" required maxlength="500" placeholder="Contoh: Ganti oli dan rem blong"></textarea>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Biaya jasa</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="biaya_jasa" class="form-control" required inputmode="numeric" autocomplete="off" data-rupiah placeholder="Contoh: 150.000">
                            </div>
                            <div class="form-text">Suku cadang ditambahkan nanti di halaman Detail.</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Mekanik (opsional)</label>
                            <select name="id_mekanik" class="form-select">
                                <option value="">-- Belum ditentukan --</option>
                                <?php foreach ($daftar_mekanik as $m): ?>
                                    <option value="<?= h($m['id']); ?>"><?= h($m['nama']); ?> (<?= h($m['id']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Tanggal masuk</label>
                            <input type="date" name="tanggal_masuk" class="form-control" required value="<?= hariIni(); ?>">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Status awal</label>
                            <select name="status" class="form-select" required>
                                <?php foreach (STATUS_KERJA as $s): ?>
                                    <option value="<?= $s; ?>"><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Pembayaran</label>
                            <select name="status_bayar" class="form-select" required>
                                <?php foreach (STATUS_BAYAR as $s): ?>
                                    <option value="<?= $s; ?>"><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal edit (diisi otomatis oleh assets/js/app.js dari atribut data-* tombol Edit) -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="judulModalEdit" aria-hidden="true" data-isi-form>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalEdit">Edit data servis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label">ID servis</label>
                            <input type="text" name="id" class="form-control" readonly>
                        </div>
                        <div class="col-sm-8">
                            <label class="form-label">Kendaraan</label>
                            <select name="id_kendaraan" class="form-select" required>
                                <?php foreach ($daftar_kendaraan as $k): ?>
                                    <option value="<?= h($k['id']); ?>"><?= h($k['nopol']); ?> - <?= h($k['merk']); ?> (<?= h($k['pemilik']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keluhan</label>
                            <textarea name="keluhan" class="form-control" rows="2" required maxlength="500"></textarea>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Biaya jasa</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="biaya_jasa" class="form-control" required inputmode="numeric" autocomplete="off" data-rupiah>
                            </div>
                            <div class="form-text">Total biaya = biaya jasa + suku cadang (diatur di halaman Detail).</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Mekanik</label>
                            <select name="id_mekanik" class="form-select">
                                <option value="">-- Belum ditentukan --</option>
                                <?php foreach ($daftar_mekanik as $m): ?>
                                    <option value="<?= h($m['id']); ?>"><?= h($m['nama']); ?> (<?= h($m['id']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Tanggal masuk</label>
                            <input type="date" name="tanggal_masuk" class="form-control" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <?php foreach (STATUS_KERJA as $s): ?>
                                    <option value="<?= $s; ?>"><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label">Pembayaran</label>
                            <select name="status_bayar" class="form-select" required>
                                <?php foreach (STATUS_BAYAR as $s): ?>
                                    <option value="<?= $s; ?>"><?= $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ubah" class="btn btn-primary">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ubah status cepat -->
<div class="modal fade" id="modalStatus" tabindex="-1" aria-labelledby="judulModalStatus" aria-hidden="true" data-isi-form>
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalStatus">Ubah status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID servis</label>
                        <input type="text" name="id" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status pekerjaan</label>
                        <select name="status" class="form-select">
                            <?php foreach (STATUS_KERJA as $s): ?>
                                <option value="<?= $s; ?>"><?= $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Pembayaran</label>
                        <select name="status_bayar" class="form-select">
                            <?php foreach (STATUS_BAYAR as $s): ?>
                                <option value="<?= $s; ?>"><?= $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="ubah_status" class="btn btn-primary w-100">Simpan status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
