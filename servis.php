<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['tambah'])) {
    $id           = mysqli_real_escape_string($conn, $_POST['id']);
    $id_kendaraan = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);
    $keluhan      = mysqli_real_escape_string($conn, $_POST['keluhan']);
    $biaya        = mysqli_real_escape_string($conn, $_POST['biaya']);
    $status       = mysqli_real_escape_string($conn, $_POST['status']);

    $cek = mysqli_query($conn, "SELECT * FROM servis WHERE id='$id'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "ID Servis sudah digunakan!";
    } else {
        mysqli_query($conn, "INSERT INTO servis VALUES ('$id', '$id_kendaraan', '$keluhan', '$status', '$biaya')");
        header("Location: servis.php");
        exit;
    }
}

if (isset($_POST['ubah_status'])) {
    $id_ubah = mysqli_real_escape_string($conn, $_POST['id_servis']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    mysqli_query($conn, "UPDATE servis SET status='$status_baru' WHERE id='$id_ubah'");
    header("Location: servis.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM servis WHERE id='$id_hapus'");
    header("Location: servis.php");
    exit;
}

$keyword = $_GET['cari'] ?? '';
$sort    = $_GET['sort'] ?? 'ASC';

$query = "SELECT servis.*, kendaraan.merk, kendaraan.nopol
          FROM servis
          JOIN kendaraan ON servis.id_kendaraan = kendaraan.id
          WHERE servis.id LIKE '%$keyword%' OR kendaraan.nopol LIKE '%$keyword%'
          ORDER BY servis.biaya $sort";
$result = mysqli_query($conn, $query);

$total_query = mysqli_query($conn, "SELECT SUM(biaya) as total_pendapatan FROM servis");
$total_pendapatan = mysqli_fetch_assoc($total_query)['total_pendapatan'] ?? 0;

$data_kendaraan = mysqli_query($conn, "SELECT id, merk, nopol FROM kendaraan ORDER BY nopol ASC");

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
                <strong>Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></strong>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah servis</button>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger py-2" role="alert"><?= $error; ?></div>
    <?php endif; ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID servis atau nopol..." aria-label="Cari servis" value="<?= htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-4">
                <select name="sort" class="form-select" aria-label="Urutan biaya">
                    <option value="ASC" <?= $sort == 'ASC' ? 'selected' : ''; ?>>Biaya: termurah ke termahal</option>
                    <option value="DESC" <?= $sort == 'DESC' ? 'selected' : ''; ?>>Biaya: termahal ke termurah</option>
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
                        <th>ID servis</th>
                        <th>Kendaraan (nopol)</th>
                        <th>Keluhan</th>
                        <th>Biaya</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="kol-id"><?= $row['id']; ?></td>
                                <td><?= $row['merk']; ?> <br><small class="text-secondary"><?= $row['nopol']; ?></small></td>
                                <td><?= $row['keluhan']; ?></td>
                                <td class="kol-rp">Rp <?= number_format($row['biaya'], 0, ',', '.'); ?></td>
                                <td><span class="st <?= kelasStatus($row['status']); ?>"><?= $row['status']; ?></span></td>
                                <td class="kol-aksi">
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $row['id']; ?>">Ubah status</button>
                                    <a href="servis.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data servis ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="gr-empty">
                                <?php if ($keyword !== ''): ?>
                                    Tidak ada servis yang cocok dengan "<?= htmlspecialchars($keyword); ?>". Coba ID servis atau nopol lain.
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

<?php
if (mysqli_num_rows($result) > 0):
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)):
?>
<div class="modal fade" id="modalStatus<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h6 class="modal-title">Ubah status <?= $row['id']; ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_servis" value="<?= $row['id']; ?>">
                    <label class="form-label">Status baru</label>
                    <select name="status_baru" class="form-select">
                        <option value="Menunggu" <?= $row['status'] == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="Dikerjakan" <?= $row['status'] == 'Dikerjakan' ? 'selected' : ''; ?>>Dikerjakan</option>
                        <option value="Selesai" <?= $row['status'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="ubah_status" class="btn btn-primary btn-sm w-100">Simpan status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
    endwhile;
endif;
?>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="judulModalTambah" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalTambah">Tambah data servis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID servis</label>
                        <input type="text" name="id" class="form-control" required placeholder="Contoh: S001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih kendaraan</label>
                        <select name="id_kendaraan" class="form-select" required>
                            <option value="" disabled selected>-- Pilih kendaraan --</option>
                            <?php while($k = mysqli_fetch_assoc($data_kendaraan)): ?>
                                <option value="<?= $k['id']; ?>"><?= $k['nopol']; ?> - <?= $k['merk']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keluhan</label>
                        <textarea name="keluhan" class="form-control" rows="2" required placeholder="Contoh: Ganti oli dan rem blong"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Biaya (Rp)</label>
                        <input type="number" name="biaya" class="form-control" required placeholder="Contoh: 150000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status awal</label>
                        <select name="status" class="form-select" required>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Dikerjakan">Dikerjakan</option>
                            <option value="Selesai">Selesai</option>
                        </select>
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
