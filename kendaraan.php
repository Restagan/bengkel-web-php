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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kendaraan - Bengkel</title>
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
                    <li class="nav-item"><a class="nav-link active" href="kendaraan.php">Data Kendaraan</a></li>
                    <li class="nav-item"><a class="nav-link" href="servis.php">Data Servis</a></li>
                    <li class="nav-item"><a class="nav-link" href="restorasi.php">Data Restorasi</a></li>
                </ul>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Data Kendaraan</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Kendaraan</button>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger py-2"><?= $error; ?></div>
        <?php endif; ?>

        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID, Merk, Nopol..." value="<?= htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="ASC" <?= $sort == 'ASC' ? 'selected' : ''; ?>>Urutkan ID: A-Z (Ascending)</option>
                    <option value="DESC" <?= $sort == 'DESC' ? 'selected' : ''; ?>>Urutkan ID: Z-A (Descending)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Kendaraan</th>
                            <th>Pemilik</th>
                            <th>Merk / Tipe</th>
                            <th>No. Polisi</th>
                            <th>Tahun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><strong><?= $row['id']; ?></strong></td>
                                    <td><?= $row['nama_pemilik']; ?> <small class="text-muted">(<?= $row['id_pelanggan']; ?>)</small></td>
                                    <td><?= $row['merk']; ?></td>
                                    <td><?= $row['nopol']; ?></td>
                                    <td><?= $row['tahun']; ?></td>
                                    <td>
                                        <a href="kendaraan.php?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kendaraan ini? Semua riwayat servis dan restorasinya juga akan ikut terhapus.')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Belum ada data kendaraan.</td>
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
                        <h5 class="modal-title">Tambah Kendaraan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ID Kendaraan</label>
                            <input type="text" name="id" class="form-control" required placeholder="Contoh: K001">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Pilih Pemilik (Pelanggan)</label>
                            <select name="id_pelanggan" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Pemilik --</option>
                                <?php while($p = mysqli_fetch_assoc($data_pelanggan)): ?>
                                    <option value="<?= $p['id']; ?>"><?= $p['nama']; ?> (<?= $p['id']; ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Merk / Tipe</label>
                            <input type="text" name="merk" class="form-control" required placeholder="Contoh: Honda Vario 160">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Polisi</label>
                            <input type="text" name="nopol" class="form-control" required placeholder="Contoh: Z 1234 AB">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tahun Pembuatan</label>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>