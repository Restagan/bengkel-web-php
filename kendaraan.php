<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['tambah'])) {
    $id           = mysqli_real_escape_string($conn, $_POST['id']);
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $merk         = mysqli_real_escape_string($conn, $_POST['merk']);
    $nopol        = mysqli_real_escape_string($conn, $_POST['nopol']);
    $tahun        = mysqli_real_escape_string($conn, $_POST['tahun']);

    $cek = mysqli_query($conn, "SELECT * FROM kendaraan WHERE id='$id'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "ID Kendaraan sudah digunakan! Gunakan ID lain.";
    } else {
        mysqli_query($conn, "INSERT INTO kendaraan VALUES ('$id', '$id_pelanggan', '$merk', '$nopol', '$tahun')");
        header("Location: kendaraan.php");
        exit;
    }
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kendaraan WHERE id='$id_hapus'");
    header("Location: kendaraan.php");
    exit;
}

$keyword = $_GET['cari'] ?? '';
$sort    = $_GET['sort'] ?? 'ASC';

$query = "SELECT kendaraan.*, pelanggan.nama AS nama_pemilik
          FROM kendaraan
          JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
          WHERE kendaraan.id LIKE '%$keyword%' OR kendaraan.merk LIKE '%$keyword%' OR kendaraan.nopol LIKE '%$keyword%'
          ORDER BY kendaraan.id $sort";
$result = mysqli_query($conn, $query);

$data_pelanggan = mysqli_query($conn, "SELECT id, nama FROM pelanggan ORDER BY nama ASC");

$judul_halaman = 'Data Kendaraan';
$menu_aktif    = 'kendaraan';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Data kendaraan</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah kendaraan</button>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger py-2" role="alert"><?= $error; ?></div>
    <?php endif; ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID, merk, atau nopol..." aria-label="Cari kendaraan" value="<?= htmlspecialchars($keyword); ?>">
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
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="kol-id"><?= $row['id']; ?></td>
                                <td><?= $row['nama_pemilik']; ?> <small class="text-secondary">(<?= $row['id_pelanggan']; ?>)</small></td>
                                <td><?= $row['merk']; ?></td>
                                <td><?= $row['nopol']; ?></td>
                                <td><?= $row['tahun']; ?></td>
                                <td class="kol-aksi">
                                    <a href="kendaraan.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kendaraan ini? Semua riwayat servis dan restorasinya juga akan ikut terhapus.')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="gr-empty">
                                <?php if ($keyword !== ''): ?>
                                    Tidak ada kendaraan yang cocok dengan "<?= htmlspecialchars($keyword); ?>". Coba kata kunci lain.
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
                        <input type="text" name="id" class="form-control" required placeholder="Contoh: K001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih pemilik (pelanggan)</label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="" disabled selected>-- Pilih pemilik --</option>
                            <?php while($p = mysqli_fetch_assoc($data_pelanggan)): ?>
                                <option value="<?= $p['id']; ?>"><?= $p['nama']; ?> (<?= $p['id']; ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Merk / tipe</label>
                        <input type="text" name="merk" class="form-control" required placeholder="Contoh: Honda Vario 160">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. polisi</label>
                        <input type="text" name="nopol" class="form-control" required placeholder="Contoh: Z 1234 AB">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun pembuatan</label>
                        <input type="number" name="tahun" class="form-control" required placeholder="Contoh: 2022">
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
