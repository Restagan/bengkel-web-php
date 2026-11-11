<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

// ---------- Tambah (ID dibuat otomatis: M001, M002, ...) ----------
if (isset($_POST['tambah'])) {
    $nama      = dariPost($conn, 'nama', 100);
    $telp      = dariPost($conn, 'telp', 20);
    $spesialis = dariPost($conn, 'spesialis', 100);

    if ($nama === '' || $telp === '') {
        flash('Nama dan telepon wajib diisi.', 'danger');
    } elseif (!telpValid($_POST['telp'])) {
        flash('No. telepon tidak valid. Isi 5-20 karakter berupa angka, spasi, +, -, atau tanda kurung.', 'danger');
    } else {
        $id = idBerikutnya($conn, 'mekanik', 'M');
        mysqli_query($conn, "INSERT INTO mekanik (id, nama, telp, spesialis) VALUES ('$id', '$nama', '$telp', '$spesialis')");
        flash("Mekanik $id berhasil ditambahkan.");
    }
    pindah('mekanik.php');
}

// ---------- Ubah ----------
if (isset($_POST['ubah'])) {
    $id_mentah = potong($_POST['id'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    $nama      = dariPost($conn, 'nama', 100);
    $telp      = dariPost($conn, 'telp', 20);
    $spesialis = dariPost($conn, 'spesialis', 100);

    if ($nama === '' || $telp === '') {
        flash('Nama dan telepon wajib diisi.', 'danger');
    } elseif (!telpValid($_POST['telp'])) {
        flash('No. telepon tidak valid. Isi 5-20 karakter berupa angka, spasi, +, -, atau tanda kurung.', 'danger');
    } else {
        mysqli_query($conn, "UPDATE mekanik SET nama = '$nama', telp = '$telp', spesialis = '$spesialis' WHERE id = '$id'");
        flash("Data mekanik $id_mentah berhasil diperbarui.");
    }
    pindah('mekanik.php');
}

// ---------- Hapus (servis dan restorasi lamanya tetap ada, kolom mekaniknya menjadi kosong) ----------
if (isset($_POST['hapus'])) {
    $id_mentah = potong($_POST['id_hapus'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    mysqli_query($conn, "DELETE FROM mekanik WHERE id = '$id'");

    if (mysqli_affected_rows($conn) > 0) {
        flash("Mekanik $id_mentah telah dihapus. Servis dan restorasi miliknya tetap tersimpan tanpa nama mekanik.");
    } else {
        flash('Data mekanik tidak ditemukan.', 'danger');
    }
    pindah('mekanik.php');
}

// ---------- Daftar ----------
$keyword = potong($_GET['cari'] ?? '', 100);
$sort    = pilihan(strtoupper($_GET['sort'] ?? 'ASC'), ['ASC', 'DESC'], 'ASC');
$kw      = mysqli_real_escape_string($conn, $keyword);

$result = mysqli_query($conn, "SELECT mekanik.*,
        (SELECT COUNT(*) FROM servis WHERE servis.id_mekanik = mekanik.id AND servis.status <> 'Selesai')
      + (SELECT COUNT(*) FROM restorasi WHERE restorasi.id_mekanik = mekanik.id AND restorasi.status <> 'Selesai') AS jml_aktif,
        (SELECT COUNT(*) FROM servis WHERE servis.id_mekanik = mekanik.id AND servis.status = 'Selesai')
      + (SELECT COUNT(*) FROM restorasi WHERE restorasi.id_mekanik = mekanik.id AND restorasi.status = 'Selesai') AS jml_selesai
    FROM mekanik
    WHERE mekanik.id LIKE '%$kw%' OR mekanik.nama LIKE '%$kw%' OR mekanik.spesialis LIKE '%$kw%'
    ORDER BY LENGTH(mekanik.id) $sort, mekanik.id $sort");

$id_berikut = idBerikutnya($conn, 'mekanik', 'M');

$judul_halaman = 'Data Mekanik';
$menu_aktif    = 'mekanik';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Data mekanik</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah mekanik</button>
    </div>

    <?= tampilFlash(); ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID, nama, atau spesialis..." aria-label="Cari mekanik" value="<?= h($keyword); ?>">
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
                        <th>ID mekanik</th>
                        <th>Nama</th>
                        <th>No. telepon</th>
                        <th>Spesialis</th>
                        <th>Sedang dikerjakan</th>
                        <th>Selesai</th>
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
                                <td><?= $row['spesialis'] !== '' ? h($row['spesialis']) : '<span class="text-secondary">-</span>'; ?></td>
                                <td><?= (int)$row['jml_aktif']; ?> pekerjaan</td>
                                <td><?= (int)$row['jml_selesai']; ?> pekerjaan</td>
                                <td class="kol-aksi">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="<?= h($row['id']); ?>"
                                        data-nama="<?= h($row['nama']); ?>"
                                        data-telp="<?= h($row['telp']); ?>"
                                        data-spesialis="<?= h($row['spesialis']); ?>">Edit</button>
                                    <form method="post" class="d-inline" data-konfirmasi="Yakin ingin menghapus mekanik ini? Servis dan restorasi miliknya tetap tersimpan, hanya nama mekaniknya yang dikosongkan.">
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
                                    Tidak ada mekanik yang cocok dengan "<?= h($keyword); ?>". Coba kata kunci lain.
                                <?php else: ?>
                                    Belum ada data mekanik. Klik <strong>Tambah mekanik</strong> agar servis bisa dibagi ke mekanik.
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalTambah">Tambah mekanik baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID mekanik</label>
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
                        <label class="form-label">Spesialis (opsional)</label>
                        <input type="text" name="spesialis" class="form-control" maxlength="100" placeholder="Contoh: Mesin, kelistrikan, body dan cat">
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

<!-- Modal edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="judulModalEdit" aria-hidden="true" data-isi-form>
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalEdit">Edit data mekanik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID mekanik</label>
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
                        <label class="form-label">Spesialis (opsional)</label>
                        <input type="text" name="spesialis" class="form-control" maxlength="100">
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
