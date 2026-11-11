# Sistem Manajemen Bengkel (Web)

Versi web dari program C++ "Tugas Kelompok Bengkel". Dibuat dengan PHP (mysqli), MySQL/MariaDB, dan Bootstrap 5.
Proyek ini hanya untuk dijalankan secara lokal.

## Fitur

| Modul      | Fitur                                                                                                   |
|------------|---------------------------------------------------------------------------------------------------------|
| Login      | Login admin (tabel `admin`), logout                                                                     |
| Dashboard  | Total pelanggan/kendaraan/pendapatan, pendapatan bulan ini, tagihan belum dibayar, pekerjaan berjalan, stok menipis, grafik 6 bulan, daftar pekerjaan belum selesai |
| Pelanggan  | **ID otomatis (P001, P002, ...)**, tambah, **ubah**, cari, urutkan, hapus                                |
| Kendaraan  | **ID otomatis (K001, ...)**, tambah, **ubah**, cari (ID/merk/nopol/pemilik), hapus, **riwayat per kendaraan** |
| Servis     | **ID otomatis (S001, ...)**, tambah, **ubah**, **mekanik**, **tanggal masuk/selesai**, **status bayar**, filter status/bayar, urut tanggal/biaya, **suku cadang + stok**, **cetak nota** |
| Restorasi  | **ID otomatis (R001, ...)**, tambah, **ubah**, mekanik, tanggal, status bayar, filter, **cetak nota**     |
| Sparepart  | **ID otomatis (SP001, ...)**, harga, stok, batas stok minimum, **isi stok**, filter stok menipis         |
| Mekanik    | **ID otomatis (M001, ...)**, tambah, ubah, hapus, jumlah pekerjaan aktif dan selesai                      |
| Laporan    | Pendapatan per periode (bulan ini/bulan lalu/tahun ini/semua), sudah dibayar vs belum, per mekanik, **unduh CSV untuk Excel** |

Tulisan tebal = fitur baru pada versi ini.

### Cara kerja yang perlu diketahui

- **ID otomatis**: ID baru = nomor terbesar yang ada + 1 (P005 menjadi P006). Data lama dengan ID bebas (misalnya `P-ADI`) tetap aman, tidak ikut dihitung.
  Jika data **terakhir** dihapus, nomornya dipakai lagi oleh data berikutnya (hapus P006, data baru berikutnya menjadi P006 lagi). Ini wajar untuk sistem berbasis "terbesar + 1".
- **Titik ribuan otomatis**: setiap kolom biaya/harga diberi titik saat mengetik (`1500000` menjadi `1.500.000`). Hanya angka yang diterima, maksimal 13 digit. Di server, titik dibuang lagi sebelum disimpan.
- **Biaya servis** = biaya jasa + total suku cadang. Suku cadang ditambahkan di halaman **Detail** servis.
  - Menambah suku cadang langsung **mengurangi stok**. Jika stok kurang, ditolak.
  - Menghapus suku cadang dari servis **mengembalikan stok**.
  - Harga suku cadang **disalin saat dipakai**, jadi nota lama tidak berubah kalau harga sparepart naik.
  - Menghapus servis yang **belum selesai** mengembalikan stok (dianggap batal). Servis berstatus **Selesai** tidak mengembalikan stok karena suku cadangnya sudah terpakai.
  - Sparepart yang pernah dipakai di servis tidak bisa dihapus (agar nota lama tetap utuh).
- **Tanggal selesai** terisi otomatis saat status diubah menjadi Selesai, dan dikosongkan lagi jika status dikembalikan.
- **Nota**: tombol Nota membuka halaman siap cetak di tab baru (Ctrl+P). Nama, alamat, dan telepon bengkel yang tampil di nota diatur di bagian atas `fungsi.php` (`NAMA_BENGKEL`, `ALAMAT_BENGKEL`, `TELP_BENGKEL`).
- **Laporan** dihitung berdasarkan **tanggal masuk** pekerjaan. File CSV memakai pemisah titik koma (`;`) dan baris `sep=;` di awal, supaya Excel (termasuk yang berbahasa Indonesia) langsung memisahkan kolom dengan benar.

## Cara Menjalankan (XAMPP)

1. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder `bengkel-web` ke `C:\xampp\htdocs\`.
3. Siapkan database, pilih salah satu:
   - **Database baru / boleh dikosongkan**: buka `http://localhost/phpmyadmin`, tab **Import**, pilih `database.sql`, klik **Import**.
   - **Sudah punya data versi lama yang ingin dipertahankan**: pilih database `db_bengkel`, tab **SQL**, tempel seluruh isi `migrasi.sql`, klik **Kirim**. Jalankan **sekali saja**. Data lama aman, hanya kolom dan tabel baru yang ditambahkan.
4. Buka `http://localhost/bengkel-web/` di browser.
5. Login dengan akun bawaan (sama dengan program C++):
   - Username: `jawasundagoat`
   - Password: `118129`

Pengaturan koneksi database ada di `koneksi.php` (default XAMPP: host `localhost`, user `root`, tanpa password).

> Jangan membuka lewat VS Code "Go Live" (Live Server). Live Server tidak bisa menjalankan PHP. Pakai alamat XAMPP di atas.

### Muncul halaman "Database belum diperbarui"?

Artinya database `db_bengkel` masih versi lama, sedangkan program ini butuh tabel dan kolom baru
(`mekanik`, `sparepart`, `servis_item`, `status_bayar`, `tanggal_masuk`, dll). Halaman itu menyebutkan apa saja yang belum ada.
Selesaikan dengan langkah nomor 3 di atas: `migrasi.sql` (data lama dipertahankan) atau `database.sql` (database baru),
lalu muat ulang halaman. Tanpa pemeriksaan ini, kekurangan tersebut tampil sebagai
`Fatal error: Unknown column 'status_bayar' in 'where clause'`.

### Catatan setelah migrasi dari versi lama

- Semua servis lama: biaya lama dianggap **biaya jasa**, tanggal masuk diisi **tanggal migrasi**, status bayar **Belum Lunas**. Rapikan lewat tombol Edit atau Status.
- Tabel sparepart masih kosong. Isi daftar dan stoknya lewat menu **Sparepart** sebelum mencatat suku cadang di servis.

## Struktur File

```
bengkel-web/
├── database.sql            skema lengkap untuk database BARU
├── migrasi.sql             untuk database lama yang datanya ingin dipertahankan
├── koneksi.php             koneksi MySQL (memuat fungsi.php)
├── fungsi.php              fungsi bantu: ID otomatis, format rupiah, validasi, stok, data bengkel untuk nota
├── index.php               halaman login
├── logout.php              keluar
├── dashboard.php           ringkasan data dan grafik
├── pelanggan.php           modul pelanggan
├── kendaraan.php           modul kendaraan
├── riwayat.php             riwayat servis dan restorasi satu kendaraan
├── servis.php              daftar servis
├── servis_detail.php       suku cadang yang dipakai satu servis (mengatur stok)
├── restorasi.php           modul restorasi
├── sparepart.php           stok suku cadang
├── mekanik.php             data mekanik
├── laporan.php             laporan pendapatan + unduh CSV
├── nota.php                nota cetak servis / restorasi
├── partials/
│   ├── header.php          <head>, navbar, dan strip aksen (dipakai semua halaman)
│   └── footer.php          footer, Bootstrap, dan assets/js/app.js
└── assets/
    ├── css/tema-gr.css     seluruh tema tampilan balap (warna, tombol, tabel, form)
    ├── js/app.js           titik ribuan otomatis, isi otomatis form edit, konfirmasi hapus
    └── img/                tempat logo opsional (logo.png)
```

## Tema Tampilan

Tampilan bertema balap: hitam, merah, dan putih dengan garis miring ala livery mobil balap.
Tema ini hanya meniru warna dan gayanya, bukan logo atau merek resmi.

- **Ganti warna:** ubah variabel `--gr-red`, `--gr-asphalt`, dan lainnya di bagian `:root` pada `assets/css/tema-gr.css`.
- **Ganti kemiringan garis:** ubah `--gr-slant` (default `-22deg`) di file yang sama.
- **Tambah logo sendiri:** taruh gambar bernama `logo.png` di folder `assets/img/`.
  Logo akan tampil otomatis di navbar dan halaman login. Tanpa file itu, yang tampil hanya penanda garis miring.
- **Ubah menu atau judul tab:** edit `partials/header.php`. Cukup satu tempat untuk semua halaman.
- **Koneksi internet:** font (Google Fonts) dan Bootstrap dimuat dari internet.
  Tanpa internet, tampilan tetap jalan memakai font bawaan sistem.

## Catatan Penting: Penghapusan Berantai (CASCADE)

Relasi antar tabel memakai `FOREIGN KEY`:

- Menghapus **pelanggan** ikut menghapus semua **kendaraan** miliknya.
- Menghapus **kendaraan** ikut menghapus semua **servis**, **restorasi**, dan suku cadang yang tercatat di servisnya.
- Menghapus **mekanik** **tidak** menghapus servis/restorasi miliknya; kolom mekaniknya hanya menjadi kosong.
- Menghapus **sparepart** yang pernah dipakai servis ditolak.

Akibatnya total pendapatan di dashboard ikut berkurang saat pelanggan/kendaraan dihapus. Ini sengaja, supaya tidak ada data yatim.
Perilaku ini berbeda dengan program C++ yang hanya menghapus satu baris data. Karena itu tombol Hapus menampilkan peringatan sebelum menghapus.
Saat pelanggan/kendaraan dihapus, stok dikembalikan hanya untuk servis yang **belum selesai**.

## Keamanan Dasar

Proyek ini lokal, tetapi tetap diamankan sewajarnya:

- Semua isian form di-escape sebelum masuk query (`dariPost()` di `fungsi.php`), jadi tanda petik seperti `D'Angelo` tidak merusak query.
- Semua teks yang tampil di halaman melewati `h()` (`htmlspecialchars`), jadi `<script>` yang diketik di form hanya tampil sebagai teks.
- Penghapusan memakai tombol **POST** (bukan link `?hapus=`), sehingga tidak bisa terpicu hanya dengan membuka alamat.
- Pilihan status, urutan, dan filter hanya menerima nilai dari daftar yang diizinkan.
- Sel CSV yang diawali `=`, `+`, `-`, atau `@` diberi tanda petik agar tidak dibaca Excel sebagai rumus.

Catatan: query masih berupa teks yang di-escape, belum *prepared statement*. Itu cukup untuk proyek lokal, tetapi bila suatu saat dipublikasikan ke internet, ganti ke prepared statement.
