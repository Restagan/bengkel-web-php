<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

if (isset($_POST['tambah'])) {
    $nama   = dariPost($conn, 'nama', 100);
    $telp   = dariPost($conn, 'telp', 20);
    $alamat = dariPost($conn, 'alamat', 500);

    if ($nama === '' || $telp === '' || $alamat === '') {
        flash('Nama, telepon, dan alamat wajib diisi.', 'danger');
    } elseif (!telpValid($_POST['telp'])) {
        flash('No. telepon tidak valid. Isi 5-20 karakter berupa angka, spasi, +, -, atau tanda kurung.', 'danger');
    } else {
        $id = idBerikutnya($conn, 'pelanggan', 'P');
        mysqli_query($conn, "INSERT INTO pelanggan (id, nama, telp, alamat) VALUES ('$id', '$nama', '$telp', '$alamat')");
        flash("Pelanggan $id berhasil ditambahkan.");
    }
    pindah('pelanggan.php');
}

if (isset($_POST['ubah'])) {
    $id_mentah = potong($_POST['id'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    $nama      = dariPost($conn, 'nama', 100);
    $telp      = dariPost($conn, 'telp', 20);
    $alamat    = dariPost($conn, 'alamat', 500);

    if ($nama === '' || $telp === '' || $alamat === '') {
        flash('Nama, telepon, dan alamat wajib diisi.', 'danger');
    } elseif (!telpValid($_POST['telp'])) {
        flash('No. telepon tidak valid. Isi 5-20 karakter berupa angka, spasi, +, -, atau tanda kurung.', 'danger');
    } else {
        mysqli_query($conn, "UPDATE pelanggan SET nama = '$nama', telp = '$telp', alamat = '$alamat' WHERE id = '$id'");
        flash("Data pelanggan $id_mentah berhasil diperbarui.");
    }
    pindah('pelanggan.php');
}

if (isset($_POST['hapus'])) {
    $id_mentah = potong($_POST['id_hapus'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);

    kembalikanStokServisBelumSelesai($conn, "id_kendaraan IN (SELECT id FROM kendaraan WHERE id_pelanggan = '$id')");
    mysqli_query($conn, "DELETE FROM pelanggan WHERE id = '$id'");

    if (mysqli_affected_rows($conn) > 0) {
        flash("Pelanggan $id_mentah beserta kendaraan dan riwayatnya telah dihapus.");
    } else {
        flash('Data pelanggan tidak ditemukan.', 'danger');
    }
    pindah('pelanggan.php');
}

$keyword = potong($_GET['cari'] ?? '', 100);
$sort    = pilihan(strtoupper($_GET['sort'] ?? 'ASC'), ['ASC', 'DESC'], 'ASC');
$kw      = mysqli_real_escape_string($conn, $keyword);

$result = mysqli_query($conn, "SELECT pelanggan.*,
        (SELECT COUNT(*) FROM kendaraan WHERE kendaraan.id_pelanggan = pelanggan.id) AS jml_kendaraan
    FROM pelanggan
    WHERE pelanggan.id LIKE '%$kw%' OR pelanggan.nama LIKE '%$kw%' OR pelanggan.telp LIKE '%$kw%'
    ORDER BY LENGTH(pelanggan.id) $sort, pelanggan.id $sort");

$id_berikut = idBerikutnya($conn, 'pelanggan', 'P');

$judul_halaman = 'Data Pelanggan';
$menu_aktif    = 'pelanggan';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Data pelanggan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah pelanggan</button>
    </div>

    <?= tampilFlash(); ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID, nama, atau telepon..." aria-label="Cari pelanggan" value="<?= h($keyword); ?>">
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
                        <th>ID pelanggan</th>
                        <th>Nama lengkap</th>
                        <th>No. telepon</th>
                        <th>Alamat</th>
                        <th>Kendaraan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="kol-id"><?= h($row['id']); ?></td>
                                <td><?= h($row['nama']); ?></td>
                                <td><?= h($row['telp']); ?></td>
                                <td><?= h($row['alamat']); ?></td>
                                <td>
                                    <?php if ($row['jml_kendaraan'] > 0): ?>
                                        <a href="kendaraan.php?cari=<?= urlencode($row['id']); ?>"><?= (int)$row['jml_kendaraan']; ?> unit</a>
                                    <?php else: ?>
                                        <span class="text-secondary">Belum ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="kol-aksi">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="<?= h($row['id']); ?>"
                                        data-nama="<?= h($row['nama']); ?>"
                                        data-telp="<?= h($row['telp']); ?>"
                                        data-alamat="<?= h($row['alamat']); ?>">Edit</button>
                                    <form method="post" class="d-inline" data-konfirmasi="Yakin ingin menghapus pelanggan ini? Semua kendaraan miliknya beserta riwayat servis dan restorasinya juga akan ikut terhapus.">
                                        <input type="hidden" name="id_hapus" value="<?= h($row['id']); ?>">
                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="gr-empty">
                                <?php if ($keyword !== ''): ?>
                                    Tidak ada pelanggan yang cocok dengan "<?= h($keyword); ?>". Coba kata kunci lain.
                                <?php else: ?>
                                    Belum ada data pelanggan. Klik <strong>Tambah pelanggan</strong> untuk menambahkan yang pertama.
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
                    <h5 class="modal-title" id="judulModalTambah">Tambah pelanggan baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID pelanggan</label>
                        <input type="text" class="form-control" value="<?= h($id_berikut); ?>" readonly>
                        <div class="form-text">Dibuat otomatis berurutan.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="nama" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. telepon</label>
                        <input type="text" name="telp" class="form-control" required maxlength="20" inputmode="tel" pattern="[0-9+\-\s\(\)]{5,20}" title="Angka, spasi, +, -, atau tanda kurung (5-20 karakter)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" required maxlength="500"></textarea>
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
                    <h5 class="modal-title" id="judulModalEdit">Edit data pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID pelanggan</label>
                        <input type="text" name="id" class="form-control" readonly>
                        <div class="form-text">ID tidak bisa diubah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="nama" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. telepon</label>
                        <input type="text" name="telp" class="form-control" required maxlength="20" inputmode="tel" pattern="[0-9+\-\s\(\)]{5,20}" title="Angka, spasi, +, -, atau tanda kurung (5-20 karakter)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" required maxlength="500"></textarea>
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
