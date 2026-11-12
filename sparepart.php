<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

if (isset($_POST['tambah'])) {
    $nama    = dariPost($conn, 'nama', 100);
    $satuan  = dariPost($conn, 'satuan', 20) ?: 'pcs';
    $harga   = angka($_POST['harga'] ?? '');
    $stok    = bilangan($_POST['stok'] ?? '');
    $minimum = bilangan($_POST['stok_minimum'] ?? '');

    if ($nama === '') {
        flash('Nama sparepart wajib diisi.', 'danger');
    } elseif (!nominalValid($harga)) {
        flash('Harga terlalu besar.', 'danger');
    } else {
        $id = idBerikutnya($conn, 'sparepart', 'SP');
        mysqli_query($conn, "INSERT INTO sparepart (id, nama, satuan, harga, stok, stok_minimum) VALUES ('$id', '$nama', '$satuan', '$harga', $stok, $minimum)");
        flash("Sparepart $id berhasil ditambahkan.");
    }
    pindah('sparepart.php');
}

if (isset($_POST['ubah'])) {
    $id_mentah = potong($_POST['id'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    $nama      = dariPost($conn, 'nama', 100);
    $satuan    = dariPost($conn, 'satuan', 20) ?: 'pcs';
    $harga     = angka($_POST['harga'] ?? '');
    $stok      = bilangan($_POST['stok'] ?? '');
    $minimum   = bilangan($_POST['stok_minimum'] ?? '');

    if ($nama === '') {
        flash('Nama sparepart wajib diisi.', 'danger');
    } elseif (!nominalValid($harga)) {
        flash('Harga terlalu besar.', 'danger');
    } else {
        mysqli_query($conn, "UPDATE sparepart SET nama = '$nama', satuan = '$satuan', harga = '$harga', stok = $stok, stok_minimum = $minimum WHERE id = '$id'");
        flash("Data sparepart $id_mentah berhasil diperbarui.");
    }
    pindah('sparepart.php');
}

if (isset($_POST['isi_stok'])) {
    $id_mentah = potong($_POST['id'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);
    $jumlah    = bilangan($_POST['jumlah'] ?? '');

    if ($jumlah < 1) {
        flash('Jumlah stok masuk minimal 1.', 'danger');
    } else {
        mysqli_query($conn, "UPDATE sparepart SET stok = stok + $jumlah WHERE id = '$id'");
        flash("Stok sparepart $id_mentah bertambah $jumlah.");
    }
    pindah('sparepart.php');
}

if (isset($_POST['hapus'])) {
    $id_mentah = potong($_POST['id_hapus'] ?? '', 50);
    $id        = mysqli_real_escape_string($conn, $id_mentah);

    $dipakai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
        (SELECT COUNT(*) FROM servis_item WHERE id_sparepart = '$id')
        + (SELECT COUNT(*) FROM penjualan_item WHERE id_sparepart = '$id') AS jumlah"));
    if ($dipakai['jumlah'] > 0) {
        flash("Sparepart $id_mentah sudah dipakai pada servis atau penjualan sehingga tidak bisa dihapus. Ubah stoknya menjadi 0 jika sudah tidak dijual.", 'danger');
    } else {
        mysqli_query($conn, "DELETE FROM sparepart WHERE id = '$id'");
        if (mysqli_affected_rows($conn) > 0) {
            flash("Sparepart $id_mentah telah dihapus.");
        } else {
            flash('Data sparepart tidak ditemukan.', 'danger');
        }
    }
    pindah('sparepart.php');
}

$keyword = potong($_GET['cari'] ?? '', 100);
$kondisi = pilihan($_GET['kondisi'] ?? '', ['', 'menipis', 'kritis'], '');
$urut    = pilihan($_GET['urut'] ?? 'id', ['id', 'nama', 'stok'], 'id');
$kw      = mysqli_real_escape_string($conn, $keyword);

$urut_sql = [
    'id'   => 'LENGTH(sparepart.id) ASC, sparepart.id ASC',
    'nama' => 'sparepart.nama ASC',
    'stok' => 'sparepart.stok ASC, sparepart.nama ASC',
];
$syarat_stok = '';
if ($kondisi === 'menipis') { $syarat_stok = 'AND sparepart.stok <= sparepart.stok_minimum'; }
if ($kondisi === 'kritis')  { $syarat_stok = 'AND sparepart.stok <= ' . (int)BATAS_STOK_KRITIS; }

$result = mysqli_query($conn, "SELECT * FROM sparepart
    WHERE (sparepart.id LIKE '%$kw%' OR sparepart.nama LIKE '%$kw%') $syarat_stok
    ORDER BY {$urut_sql[$urut]}");

$id_berikut = idBerikutnya($conn, 'sparepart', 'SP');

$judul_halaman = 'Data Sparepart';
$menu_aktif    = 'sparepart';
include 'partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h1 class="gr-title">Sparepart dan stok</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">Tambah sparepart</button>
    </div>

    <?= tampilFlash(); ?>

    <form method="get" class="gr-toolbar">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="cari" class="form-control" placeholder="Cari ID atau nama sparepart..." aria-label="Cari sparepart" value="<?= h($keyword); ?>">
            </div>
            <div class="col-md-3">
                <select name="kondisi" class="form-select" aria-label="Kondisi stok">
                    <option value="" <?= $kondisi === '' ? 'selected' : ''; ?>>Semua stok</option>
                    <option value="menipis" <?= $kondisi === 'menipis' ? 'selected' : ''; ?>>Stok menipis atau habis</option>
                    <option value="kritis" <?= $kondisi === 'kritis' ? 'selected' : ''; ?>>Stok kritis (sisa <?= (int)BATAS_STOK_KRITIS; ?> atau kurang)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="urut" class="form-select" aria-label="Urutan">
                    <option value="id" <?= $urut === 'id' ? 'selected' : ''; ?>>Urutkan: ID</option>
                    <option value="nama" <?= $urut === 'nama' ? 'selected' : ''; ?>>Urutkan: nama</option>
                    <option value="stok" <?= $urut === 'stok' ? 'selected' : ''; ?>>Urutkan: stok tersedikit</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="gr-panel">
        <div class="table-responsive">
            <table class="table table-gr table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama sparepart</th>
                        <th>Harga satuan</th>
                        <th>Stok</th>
                        <th>Batas minimum</th>
                        <th>Kondisi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <?php [$kelas_stok, $teks_stok] = kondisiStok((int)$row['stok'], (int)$row['stok_minimum']); ?>
                            <tr>
                                <td class="kol-id"><?= h($row['id']); ?></td>
                                <td><?= h($row['nama']); ?></td>
                                <td class="kol-rp"><?= rupiah($row['harga']); ?></td>
                                <td><span class="kol-rp"><?= (int)$row['stok']; ?></span> <?= h($row['satuan']); ?></td>
                                <td><?= (int)$row['stok_minimum']; ?> <?= h($row['satuan']); ?></td>
                                <td><span class="st <?= $kelas_stok; ?>"><?= $teks_stok; ?></span></td>
                                <td class="kol-aksi">
                                    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalStok"
                                        data-id="<?= h($row['id']); ?>"
                                        data-nama="<?= h($row['nama']); ?>">Isi stok</button>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit"
                                        data-id="<?= h($row['id']); ?>"
                                        data-nama="<?= h($row['nama']); ?>"
                                        data-satuan="<?= h($row['satuan']); ?>"
                                        data-harga="<?= digitUang($row['harga']); ?>"
                                        data-stok="<?= (int)$row['stok']; ?>"
                                        data-stok_minimum="<?= (int)$row['stok_minimum']; ?>">Edit</button>
                                    <form method="post" class="d-inline" data-konfirmasi="Yakin ingin menghapus sparepart ini?">
                                        <input type="hidden" name="id_hapus" value="<?= h($row['id']); ?>">
                                        <button type="submit" name="hapus" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="gr-empty">
                                <?php if ($keyword !== '' || $kondisi !== ''): ?>
                                    Tidak ada sparepart yang cocok dengan filter ini.
                                <?php else: ?>
                                    Belum ada data sparepart. Klik <strong>Tambah sparepart</strong> untuk mulai mencatat stok.
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
                    <h5 class="modal-title" id="judulModalTambah">Tambah sparepart baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID sparepart</label>
                        <input type="text" class="form-control" value="<?= h($id_berikut); ?>" readonly>
                        <div class="form-text">Dibuat otomatis berurutan.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama sparepart</label>
                        <input type="text" name="nama" class="form-control" required maxlength="100" placeholder="Contoh: Oli mesin 1 liter">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label">Harga satuan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="harga" class="form-control" required inputmode="numeric" autocomplete="off" data-rupiah placeholder="Contoh: 85.000">
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="satuan" class="form-control" maxlength="20" value="pcs">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Stok awal</label>
                            <input type="number" name="stok" class="form-control" required min="0" value="0">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Batas stok minimum</label>
                            <input type="number" name="stok_minimum" class="form-control" required min="0" value="5">
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

<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="judulModalEdit" aria-hidden="true" data-isi-form>
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalEdit">Edit data sparepart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ID sparepart</label>
                        <input type="text" name="id" class="form-control" readonly>
                        <div class="form-text">ID tidak bisa diubah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama sparepart</label>
                        <input type="text" name="nama" class="form-control" required maxlength="100">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label">Harga satuan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="harga" class="form-control" required inputmode="numeric" autocomplete="off" data-rupiah>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Satuan</label>
                            <input type="text" name="satuan" class="form-control" maxlength="20">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Stok saat ini</label>
                            <input type="number" name="stok" class="form-control" required min="0">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Batas stok minimum</label>
                            <input type="number" name="stok_minimum" class="form-control" required min="0">
                        </div>
                    </div>
                    <div class="form-text mt-2">Mengubah harga tidak memengaruhi nota yang sudah dibuat.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ubah" class="btn btn-primary">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalStok" tabindex="-1" aria-labelledby="judulModalStok" aria-hidden="true" data-isi-form>
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulModalStok">Isi stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sparepart</label>
                        <input type="text" name="nama" class="form-control" readonly>
                        <input type="hidden" name="id">
                    </div>
                    <div>
                        <label class="form-label">Jumlah barang masuk</label>
                        <input type="number" name="jumlah" class="form-control" required min="1" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="isi_stok" class="btn btn-primary w-100">Tambah ke stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
