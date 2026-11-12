<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['admin_bengkel'])) {
    pindah('index.php');
}

$jenis     = pilihan($_GET['jenis'] ?? '', ['servis', 'restorasi', 'penjualan'], 'servis');
$id_mentah = potong($_GET['id'] ?? '', 50);
$id        = mysqli_real_escape_string($conn, $id_mentah);
$uraian    = $jenis === 'servis' ? 'keluhan' : 'deskripsi';

if ($jenis === 'penjualan') {
    $nota = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM penjualan WHERE id = '$id'"));
} else {
    $nota = mysqli_fetch_assoc(mysqli_query($conn, "SELECT $jenis.*, kendaraan.merk, kendaraan.nopol, kendaraan.tahun,
            pelanggan.nama AS nama_pelanggan, pelanggan.telp AS telp_pelanggan, mekanik.nama AS nama_mekanik
        FROM $jenis
        JOIN kendaraan ON $jenis.id_kendaraan = kendaraan.id
        JOIN pelanggan ON kendaraan.id_pelanggan = pelanggan.id
        LEFT JOIN mekanik ON $jenis.id_mekanik = mekanik.id
        WHERE $jenis.id = '$id'"));
}

if (!$nota) {
    flash('Data untuk nota tidak ditemukan.', 'danger');
    pindah($jenis === 'penjualan' ? 'kasir.php' : $jenis . '.php');
}

$halaman_kembali = $jenis === 'penjualan' ? 'kasir.php' : $jenis . '.php';
$total_nota      = $jenis === 'penjualan' ? $nota['total'] : $nota['biaya'];

$baris = [];
if ($jenis === 'servis') {
    $baris[] = ['Jasa servis: ' . $nota['keluhan'], '1', $nota['biaya_jasa'], $nota['biaya_jasa']];
    $hasil = mysqli_query($conn, "SELECT servis_item.jumlah, servis_item.harga_satuan, sparepart.nama, sparepart.satuan
        FROM servis_item JOIN sparepart ON servis_item.id_sparepart = sparepart.id
        WHERE servis_item.id_servis = '$id' ORDER BY servis_item.id ASC");
    while ($r = mysqli_fetch_assoc($hasil)) {
        $baris[] = [$r['nama'], (int)$r['jumlah'] . ' ' . $r['satuan'], $r['harga_satuan'], $r['jumlah'] * $r['harga_satuan']];
    }
} elseif ($jenis === 'penjualan') {
    $hasil = mysqli_query($conn, "SELECT penjualan_item.jumlah, penjualan_item.harga_satuan, sparepart.nama, sparepart.satuan
        FROM penjualan_item JOIN sparepart ON penjualan_item.id_sparepart = sparepart.id
        WHERE penjualan_item.id_penjualan = '$id' ORDER BY penjualan_item.id ASC");
    while ($r = mysqli_fetch_assoc($hasil)) {
        $baris[] = [$r['nama'], (int)$r['jumlah'] . ' ' . $r['satuan'], $r['harga_satuan'], $r['jumlah'] * $r['harga_satuan']];
    }
} else {
    $baris[] = ['Restorasi: ' . $nota['deskripsi'], '1', $nota['biaya'], $nota['biaya']];
}

$nama_jenis = ['servis' => 'Servis', 'restorasi' => 'Restorasi', 'penjualan' => 'Penjualan'][$jenis];
$judul_nota = 'Nota ' . $nama_jenis . ' ' . $nota['id'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($judul_nota); ?></title>
    <style>
        :root { --merah: #eb0a1e; --tinta: #16161a; --abu: #6b6b73; --garis: #d9d9de; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8e8ec; color: var(--tinta); font: 14px/1.5 "Segoe UI", Roboto, Arial, sans-serif; }
        .alat { max-width: 780px; margin: 16px auto 0; padding: 0 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .alat button, .alat a { font: inherit; font-weight: 600; padding: 8px 18px; border: 0; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .alat .utama { background: var(--merah); color: #fff; }
        .alat .biasa { background: #fff; color: var(--tinta); border: 1px solid var(--garis); }
        .kertas { max-width: 780px; margin: 16px auto; background: #fff; padding: 32px 36px; border-top: 6px solid var(--merah); box-shadow: 0 2px 12px rgba(0,0,0,.12); }
        .kop { display: flex; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding-bottom: 16px; border-bottom: 2px solid var(--tinta); }
        .kop h1 { margin: 0; font-size: 26px; letter-spacing: .04em; text-transform: uppercase; font-style: italic; }
        .kop p { margin: 0; color: var(--abu); }
        .kop .no { text-align: right; }
        .kop .no strong { display: block; font-size: 18px; color: var(--merah); }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .info h2 { margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: .12em; color: var(--abu); }
        .info p { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .1em; color: var(--abu); border-bottom: 1px solid var(--tinta); padding: 6px 8px; }
        td { padding: 8px; border-bottom: 1px solid var(--garis); vertical-align: top; }
        .kanan { text-align: right; white-space: nowrap; }
        tfoot td { border-bottom: 0; font-weight: 700; font-size: 16px; padding-top: 12px; }
        tfoot .rinci td { font-weight: 400; font-size: 14px; padding-top: 2px; }
        .bawah { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; flex-wrap: wrap; margin-top: 28px; }
        .cap { display: inline-block; border: 3px solid; padding: 4px 16px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; transform: rotate(-4deg); font-size: 18px; }
        .cap.lunas { color: #12803c; border-color: #12803c; }
        .cap.belum { color: var(--merah); border-color: var(--merah); }
        .tanda { display: flex; gap: 48px; text-align: center; color: var(--abu); font-size: 12px; }
        .tanda div { width: 130px; }
        .tanda span { display: block; height: 56px; border-bottom: 1px solid var(--tinta); margin-bottom: 4px; }
        .lacak { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 24px; padding: 12px 16px; border: 1px dashed var(--abu); }
        .lacak p { margin: 0; color: var(--abu); font-size: 12px; }
        .lacak strong { display: block; font-size: 20px; letter-spacing: .1em; color: var(--tinta); font-family: Consolas, "Courier New", monospace; }
        .terima { margin-top: 24px; text-align: center; color: var(--abu); font-size: 12px; }
        @media (max-width: 600px) {
            .kertas { padding: 20px 16px; }
            .info { grid-template-columns: 1fr; }
            .kop .no { text-align: left; }
        }
        @media print {
            body { background: #fff; }
            .alat { display: none; }
            .kertas { margin: 0; max-width: none; box-shadow: none; padding: 0; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="alat">
        <button type="button" class="utama" id="tombolCetak">Cetak nota</button>
        <a href="<?= h($halaman_kembali); ?>" class="biasa">Kembali</a>
    </div>

    <main class="kertas">
        <div class="kop">
            <div>
                <h1><?= h(NAMA_BENGKEL); ?></h1>
                <p><?= h(ALAMAT_BENGKEL); ?></p>
                <p>Telp. <?= h(TELP_BENGKEL); ?></p>
            </div>
            <div class="no">
                <p>Nota <?= h(strtolower($nama_jenis)); ?></p>
                <strong><?= h($nota['id']); ?></strong>
                <?php if ($jenis === 'penjualan'): ?>
                    <p>Tanggal: <?= tanggalIndo($nota['tanggal']); ?></p>
                <?php else: ?>
                    <p>Masuk: <?= tanggalIndo($nota['tanggal_masuk']); ?></p>
                    <p>Selesai: <?= $nota['tanggal_selesai'] ? tanggalIndo($nota['tanggal_selesai']) : '-'; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="info">
            <?php if ($jenis === 'penjualan'): ?>
                <div>
                    <h2>Pembeli</h2>
                    <p><strong><?= $nota['nama_pembeli'] !== null && $nota['nama_pembeli'] !== '' ? h($nota['nama_pembeli']) : 'Pembeli umum'; ?></strong></p>
                </div>
            <?php else: ?>
                <div>
                    <h2>Pelanggan</h2>
                    <p><strong><?= h($nota['nama_pelanggan']); ?></strong></p>
                    <p><?= h($nota['telp_pelanggan']); ?></p>
                </div>
                <div>
                    <h2>Kendaraan</h2>
                    <p><strong><?= h($nota['merk']); ?></strong> (<?= h($nota['tahun']); ?>)</p>
                    <p>Nopol: <?= h($nota['nopol']); ?></p>
                    <p>Mekanik: <?= $nota['nama_mekanik'] ? h($nota['nama_mekanik']) : '-'; ?></p>
                </div>
            <?php endif; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Keterangan</th>
                    <th class="kanan">Jumlah</th>
                    <th class="kanan">Harga</th>
                    <th class="kanan">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($baris as $b): ?>
                    <tr>
                        <td><?= h($b[0]); ?></td>
                        <td class="kanan"><?= h($b[1]); ?></td>
                        <td class="kanan"><?= rupiah($b[2]); ?></td>
                        <td class="kanan"><?= rupiah($b[3]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="kanan">Total</td>
                    <td class="kanan"><?= rupiah($total_nota); ?></td>
                </tr>
                <?php if ($jenis === 'penjualan'): ?>
                    <tr class="rinci">
                        <td colspan="3" class="kanan">Uang diterima</td>
                        <td class="kanan"><?= rupiah($nota['bayar']); ?></td>
                    </tr>
                    <tr class="rinci">
                        <td colspan="3" class="kanan">Kembalian</td>
                        <td class="kanan"><?= rupiah($nota['kembalian']); ?></td>
                    </tr>
                <?php endif; ?>
            </tfoot>
        </table>

        <div class="bawah">
            <?php $status_nota = $jenis === 'penjualan' ? 'Lunas' : $nota['status_bayar']; ?>
            <span class="cap <?= $status_nota === 'Lunas' ? 'lunas' : 'belum'; ?>"><?= h($status_nota); ?></span>
            <div class="tanda">
                <div><span></span><?= $jenis === 'penjualan' ? 'Pembeli' : 'Pelanggan'; ?></div>
                <div><span></span>Hormat kami</div>
            </div>
        </div>

        <?php if ($jenis === 'servis' && !empty($nota['kode_resi'])): ?>
            <div class="lacak">
                <div>
                    <p>Kode lacak servis</p>
                    <strong><?= h($nota['kode_resi']); ?></strong>
                </div>
                <p>Pantau progres kendaraan Anda kapan saja:<br>buka <strong style="display:inline;font-size:12px;letter-spacing:0"><?= h(urlLacak()); ?></strong><br>lalu masukkan kode lacak atau nopol.</p>
            </div>
        <?php endif; ?>

        <p class="terima">Terima kasih atas kepercayaan Anda. Simpan nota ini sebagai bukti <?= $jenis === 'penjualan' ? 'pembelian' : 'servis'; ?>.</p>
    </main>

    <script>
        document.getElementById('tombolCetak').addEventListener('click', function () { window.print(); });
    </script>
</body>
</html>
