<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['tambah'])) {
    $id     = mysqli_real_escape_string($conn, $_POST['id']);
    $nama   = mysqli_real_escape_string($conn, $_POST['nama']);
    $telp   = mysqli_real_escape_string($conn, $_POST['telp']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

    $cek = mysqli_query($conn, "SELECT * FROM pelanggan WHERE id='$id'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "ID Pelanggan sudah digunakan! Gunakan ID lain.";
    } else {
        mysqli_query($conn, "INSERT INTO pelanggan VALUES ('$id', '$nama', '$telp', '$alamat')");
        header("Location: pelanggan.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pelanggan WHERE id='$id_hapus'");
    header("Location: pelanggan.php");
    exit;
}

$keyword = $_GET['cari'] ?? '';
$sort    = $_GET['sort'] ?? 'ASC';

$query = "SELECT * FROM pelanggan WHERE id LIKE '%$keyword%' OR nama LIKE '%$keyword%' ORDER BY id $sort";
$result = mysqli_query($conn, $query);

$judul_halaman = 'Data Pelanggan';
$menu_aktif    = 'pelanggan';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Data pelanggan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah pelanggan</button>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger py-2" role="alert"><?= $error; ?></div>
    <?php endif; ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID atau nama..." aria-label="Cari pelanggan" value="<?= htmlspecialchars($keyword); ?>">
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
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="kol-id"><?= $row['id']; ?></td>
                                <td><?= $row['nama']; ?></td>
                                <td><?= $row['telp']; ?></td>
                                <td><?= $row['alamat']; ?></td>
                                <td class="kol-aksi">
                                    <a href="pelanggan.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus pelanggan ini? Semua kendaraan miliknya beserta riwayat servis dan restorasinya juga akan ikut terhapus.')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="gr-empty">
                                <?php if ($keyword !== ''): ?>
                                    Tidak ada pelanggan yang cocok dengan "<?= htmlspecialchars($keyword); ?>". Coba kata kunci lain.
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
                        <input type="text" name="id" class="form-control" required placeholder="Contoh: P001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. telepon</label>
                        <input type="text" name="telp" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" required></textarea>
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

<?php include 'partials/footer.php'; ?>
