<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['tambah'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $id_kendaraan = mysqli_real_escape_string($conn, $_POST['id_kendaraan']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $biaya = mysqli_real_escape_string($conn, $_POST['biaya']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $cek = mysqli_query($conn, "SELECT * FROM restorasi WHERE id='$id'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "ID Restorasi sudah digunakan!";
    } else {
        mysqli_query($conn, "INSERT INTO restorasi VALUES ('$id', '$id_kendaraan', '$deskripsi', '$status', '$biaya')");
        header("Location: restorasi.php");
        exit;
    }
}

if (isset($_POST['ubah_status'])) {
    $id_ubah = mysqli_real_escape_string($conn, $_POST['id_restorasi']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    mysqli_query($conn, "UPDATE restorasi SET status='$status_baru' WHERE id='$id_ubah'");
    header("Location: restorasi.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM restorasi WHERE id='$id_hapus'");
    header("Location: restorasi.php");
    exit;
}

$keyword = $_GET['cari'] ?? '';
$sort = $_GET['sort'] ?? 'ASC';

$query = "SELECT restorasi.*, kendaraan.merk, kendaraan.nopol 
          FROM restorasi 
          JOIN kendaraan ON restorasi.id_kendaraan = kendaraan.id 
          WHERE restorasi.id LIKE '%$keyword%' OR kendaraan.nopol LIKE '%$keyword%'
          ORDER BY restorasi.biaya $sort";
$result = mysqli_query($conn, $query);

$total_query = mysqli_query($conn, "SELECT SUM(biaya) as total_pendapatan FROM restorasi");
$total_pendapatan = mysqli_fetch_assoc($total_query)['total_pendapatan'] ?? 0;

$data_kendaraan = mysqli_query($conn, "SELECT id, merk, nopol FROM kendaraan ORDER BY nopol ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Restorasi - Bengkel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">BENGKEL SYSTEM</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="pelanggan.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="kendaraan.php">Data Kendaraan</a></li>
                    <li class="nav-item"><a class="nav-link" href="servis.php">Data Servis</a></li>
                    <li class="nav-item"><a class="nav-link active" href="restorasi.php">Data Restorasi</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Data Restorasi</h3>
            <div>
                <span class="badge bg-success fs-6 me-2">Total Pendapatan: Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></span>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Restorasi</button>
            </div>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2"><?= $error; ?></div>
        <?php endif; ?>

        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID Restorasi atau Nopol..." value="<?= htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="ASC" <?= $sort == 'ASC' ? 'selected' : ''; ?>>Biaya: Termurah ke Termahal</option>
                    <option value="DESC" <?= $sort == 'DESC' ? 'selected' : ''; ?>>Biaya: Termahal ke Termurah</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Restorasi</th>
                            <th>Kendaraan (Nopol)</th>
                            <th>Deskripsi</th>
                            <th>Biaya</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?= $row['id']; ?></strong></td>
                                    <td><?= $row['merk']; ?> <br><small class="text-muted"><?= $row['nopol']; ?></small></td>
                                    <td><?= $row['deskripsi']; ?></td>
                                    <td>Rp <?= number_format($row['biaya'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?= $row['status'] == 'Selesai' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?= $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalStatus<?= $row['id']; ?>">Ubah Status</button>
                                        <a href="restorasi.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data restorasi ini?')">Hapus</a>
                                    </td>
                                </tr>

                                <div class="modal fade" id="modalStatus<?= $row['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <form action="" method="post">
                                                <div class="modal-header bg-info text-white">
                                                    <h6 class="modal-title">Ubah Status Restorasi</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_restorasi" value="<?= $row['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status Baru:</label>
                                                        <select name="status_baru" class="form-select">
                                                            <option value="Menunggu" <?= $row['status'] == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                                                            <option value="Dikerjakan" <?= $row['status'] == 'Dikerjakan' ? 'selected' : ''; ?>>Dikerjakan</option>
                                                            <option value="Selesai" <?= $row['status'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="ubah_status" class="btn btn-primary btn-sm w-100">Simpan Status</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Belum ada data restorasi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Data Restorasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ID Restorasi</label>
                            <input type="text" name="id" class="form-control" required placeholder="Contoh: R001">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Kendaraan</label>
                            <select name="id_kendaraan" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Kendaraan --</option>
                                <?php while($k = mysqli_fetch_assoc($data_kendaraan)): ?>
                                    <option value="<?= $k['id']; ?>"><?= $k['nopol']; ?> - <?= $k['merk']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Restorasi</label>
                            <textarea name="deskripsi" class="form-control" rows="2" required placeholder="Contoh: Cat ulang full body"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Biaya / Estimasi (Rp)</label>
                            <input type="number" name="biaya" class="form-control" required placeholder="Contoh: 5000000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Awal</label>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>