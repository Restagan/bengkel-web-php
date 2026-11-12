<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

function periksaKendaraan($conn, $id_pelanggan, $merk, $nopol, $tahun, $id_diabaikan = '') {
    if ($id_pelanggan === '' || $merk === '' || $nopol === '' || $tahun === '') {
        return 'Pemilik, merk, nomor polisi, dan tahun wajib diisi.';
    }
    $ada = mysqli_query($conn, "SELECT id FROM pelanggan WHERE id = '$id_pelanggan'");
    if (mysqli_num_rows($ada) == 0) {
        return 'Pemilik tidak ditemukan. Pilih pelanggan dari daftar.';
    }
    if (!nopolValid($nopol)) {
        return 'No. polisi tidak valid. Gunakan huruf, angka, spasi, atau tanda - (3-20 karakter).';
    }
    if (!preg_match('/^\d{4}$/', $tahun) || $tahun < 1900 || $tahun > date('Y') + 1) {
        return 'Tahun harus 4 angka antara 1900 dan ' . (date('Y') + 1) . '.';
    }
    $ganda = mysqli_query($conn, "SELECT id FROM kendaraan WHERE nopol = '$nopol' AND id <> '$id_diabaikan'");
    if (mysqli_num_rows($ganda) > 0) {
        $baris = mysqli_fetch_assoc($ganda);
        return 'No. polisi sudah terdaftar pada kendaraan ' . $baris['id'] . '.';
    }
    return '';
}

if (isset($_POST['tambah'])) {
    $id_pelanggan = dariPost($conn, 'id_pelanggan', 50);
    $merk         = dariPost($conn, 'merk', 100);
    $nopol        = mysqli_real_escape_string($conn, rapikanNopol($_POST['nopol'] ?? ''));
    $tahun        = trim($_POST['tahun'] ?? '');

    $salah = periksaKendaraan($conn, $id_pelanggan, $merk, $nopol, $tahun);
    if ($salah !== '') {
        flash($salah, 'danger');
    } else {
        $id = idBerikutnya($conn, 'kendaraan', 'K');
        mysqli_query($conn, "INSERT INTO kendaraan (id, id_pelanggan, merk, nopol, tahun) VALUES ('$id', '$id_pelanggan', '$merk', '$nopol', '$tahun')");
        flash("Kendaraan $id berhasil ditambahkan.");
    }
    pindah('kendaraan.php');
}

if (isset($_POST['ubah'])) {
    $id_mentah    = potong($_POST['id'] ?? '', 50);
    $id           = mysqli_real_escape_string($conn, $id_mentah);
    $id_pelanggan = dariPost($conn, 'id_pelanggan', 50);
    $merk         = dariPost($conn, 'merk', 100);
    $nopol        = mysqli_real_escape_string($conn, rapikanNopol($_POST['nopol'] ?? ''));
    $tahun        = trim($_POST['tahun'] ?? '');

    $salah = periksaKendaraan($conn, $id_pelanggan, $merk, $nopol, $tahun, $id);
    if ($salah !== '') {
        flash($salah, 'danger');
    } else {
        mysqli_query($conn, "UPDATE kendaraan SET id_pelanggan = '$id_pelanggan', merk = '$merk', nopol = '$nopol', tahun = '$tahun' WHERE id = '$id'");
        flash("Data kendaraan $id_mentah berhasil diperbarui.");
    }
    pindah('kendaraan.php');
}

if (isset($_POST['hapus'])) {
    $id_mentah = potong($_POST['id_hapus'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);

    kembalikanStokServisBelumSelesai($conn, "id_kendaraan = '$id'");
    mysqli_query($conn, "DELETE FROM kendaraan WHERE id = '$id'");

    if (mysqli_affected_rows($conn) > 0) {
        flash("Kendaraan $id_mentah beserta riwayat servis dan restorasinya telah dihapus.");
    } else {
        flash('Data kendaraan tidak ditemukan.', 'danger');
    }
    pindah('kendaraan.php');
}

$keyword = potong($_GET['cari'] ?? '', 100);
$sort    = pilihan(strtoupper($_GET['sort'] ?? 'ASC'), ['ASC', 'DESC'], 'ASC');
$kw      = mysqli_real_escape_string($conn, $keyword);

$result = mysqli_query($conn, "SELECT kendaraan.*, pelanggan.nama AS nama_pemilik,
        (SELECT COUNT(*) FROM servis WHERE servis.id_kendaraan = kendaraan.id) AS jml_servis,
        (SELECT COUNT(*) FROM restorasi WHERE restorasi.id_kendaraan = kendaraan.id) AS jml_restorasi
    FROM kendaraan
    JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
    WHERE kendaraan.id LIKE '%$kw%' OR kendaraan.merk LIKE '%$kw%' OR kendaraan.nopol LIKE '%$kw%'
       OR pelanggan.nama LIKE '%$kw%' OR kendaraan.id_pelanggan LIKE '%$kw%'
    ORDER BY LENGTH(kendaraan.id) $sort, kendaraan.id $sort");

$daftar_pelanggan = [];
$hasil_pelanggan  = mysqli_query($conn, "SELECT id, nama FROM pelanggan ORDER BY nama ASC");
while ($p = mysqli_fetch_assoc($hasil_pelanggan)) {
    $daftar_pelanggan[] = $p;
}

$id_berikut = idBerikutnya($conn, 'kendaraan', 'K');

$judul_halaman = 'Data Kendaraan';
$menu_aktif    = 'kendaraan';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Data kendaraan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah kendaraan</button>
    </div>

    <?= tampilFlash(); ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID, merk, nopol, atau pemilik..." aria-label="Cari kendaraan" value="<?= h($keyword); ?>">
            </div>
            <div class="col-md-4">
                <select name="sort" class="form-select" aria-label="Urutan">
                    <option value="ASC" <?= $sort == 'ASC' ? 'selected' : ''; ?>>Urutkan ID: A-Z (Ascending)</option>
                    <option value="DESC" <?= $sort == 'DESC' ? 'selected' : ''; ?>>Urutkan ID: Z-A (Descending)</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="gr-panel">
        <div class="table-responsive">
            <table class="table table-gr table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID kendaraan</th>
                        <th>Pemilik</th>
                        <th>Merk / tipe</th>
                        <th>No. polisi</th>
                        <th>Tahun</th>
                        <th>Pekerjaan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="kol-id"><?= h($row['id']); ?></td>
                                <td><?= h($row['nama_pemilik']); ?> <small class="text-secondary">(<?= h($row['id_pelanggan']); ?>)</small></td>
                                <td><?= h($row['merk']); ?></td>
                                <td><?= h($row['nopol']); ?></td>
                                <td><?= h($row['tahun']); ?></td>
                                <td>
                                    <?php if ($row['jml_servis'] + $row['jml_restorasi'] > 0): ?>
                                        <?= (int)$row['jml_servis']; ?> servis, <?= (int)$row['jml_restorasi']; ?> restorasi
                                    <?php else: ?>
                                        <span class="text-secondary">Belum ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="kol-aksi">
                                    <a href="riwayat.php?id=<?= urlencode($row['id']); ?>" class="btn btn-secondary btn-sm">Riwayat</a>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="<?= h($row['id']); ?>"
                                        data-id_pelanggan="<?= h($row['id_pelanggan']); ?>"
                                        data-merk="<?= h($row['merk']); ?>"
                                        data-nopol="<?= h($row['nopol']); ?>"
                                        data-tahun="<?= h($row['tahun']); ?>">Edit</button>
                                    <form method="post" class="d-inline" data-konfirmasi="Yakin ingin menghapus kendaraan ini? Semua riwayat servis dan restorasinya juga akan ikut terhapus.">
                                        <input type="hidden" name="id_hapus" value="<?= h($row['id']); ?>">
                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="gr-empty">
                                <?php if ($keyword !== ''): ?>
                                    Tidak ada kendaraan yang cocok dengan "<?= h($keyword); ?>". Coba kata kunci lain.
                                <?php else: ?>
                                    Belum ada data kendaraan. Tambahkan pelanggan dulu, lalu klik <strong>Tambah kendaraan</strong>.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="judulModalTambah" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalTambah">Tambah kendaraan baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID kendaraan</label>
                        <input type="text" class="form-control" value="<?= h($id_berikut); ?>" readonly>
                        <div class="form-text">Dibuat otomatis berurutan.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pemilik (pelanggan)</label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="" disabled selected><?= $daftar_pelanggan ? '-- Pilih pemilik --' : 'Belum ada pelanggan, tambahkan dulu'; ?></option>
                            <?php foreach ($daftar_pelanggan as $p): ?>
                                <option value="<?= h($p['id']); ?>"><?= h($p['nama']); ?> (<?= h($p['id']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Merk / tipe</label>
                        <input type="text" name="merk" class="form-control" required maxlength="100" placeholder="Contoh: Honda Vario 160">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. polisi</label>
                        <input type="text" name="nopol" class="form-control" required maxlength="20" placeholder="Contoh: Z 1234 AB">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun pembuatan</label>
                        <input type="number" name="tahun" class="form-control" required min="1900" max="<?= date('Y') + 1; ?>" placeholder="Contoh: 2022">
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

<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="judulModalEdit" aria-hidden="true" data-isi-form>
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalEdit">Edit data kendaraan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID kendaraan</label>
                        <input type="text" name="id" class="form-control" readonly>
                        <div class="form-text">ID tidak bisa diubah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pemilik (pelanggan)</label>
                        <select name="id_pelanggan" class="form-select" required>
                            <?php foreach ($daftar_pelanggan as $p): ?>
                                <option value="<?= h($p['id']); ?>"><?= h($p['nama']); ?> (<?= h($p['id']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Merk / tipe</label>
                        <input type="text" name="merk" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. polisi</label>
                        <input type="text" name="nopol" class="form-control" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun pembuatan</label>
                        <input type="number" name="tahun" class="form-control" required min="1900" max="<?= date('Y') + 1; ?>">
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

<?php include 'partials/footer.php'; ?>
