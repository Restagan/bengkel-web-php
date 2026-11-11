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
| Servis     | **ID otomatis (S001, ...)**, tambah, **ubah**, **mekanik**, **tanggal masuk/selesai**, **status bayar**, filter status/bayar, urut tanggal/biaya, **suku cadang + stok**, **cetak nota**, **kode resi (SRV-XXXXXX)** |
| Lacak servis | **Halaman publik `lacak.php` (tanpa login): pelanggan melacak progres dengan nopol atau kode resi, timeline Menunggu / Dikerjakan / Selesai, pembaruan otomatis** |
| Restorasi  | **ID otomatis (R001, ...)**, tambah, **ubah**, mekanik, tanggal, status bayar, filter, **cetak nota**     |
| Sparepart  | **ID otomatis (SP001, ...)**, harga, stok, batas stok minimum, **isi stok**, filter stok menipis         |
| Mekanik    | **ID otomatis (M001, ...)**, tambah, ubah, hapus, jumlah pekerjaan aktif dan selesai                      |
| Laporan    | Pendapatan per periode (bulan ini/bulan lalu/tahun ini/semua), sudah dibayar vs belum, per mekanik, **unduh CSV untuk Excel** |

Tulisan tebal = fitur terbaru.

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

## Live Tracking Progress Servis (`lacak.php`)

Pelanggan bisa memantau kendaraannya tanpa login, di `http://localhost/bengkel-web/lacak.php`
(tautannya juga ada di halaman login admin).

**Alur:**

1. Setiap servis otomatis punya **kode resi** unik, contoh `SRV-K7M2XQ` (6 karakter acak tanpa huruf/angka yang mudah tertukar: `I`, `O`, `0`, `1`).
   Kode muncul di daftar servis (di bawah ID), di halaman **Detail**, dan tercetak di **nota** beserta alamat halaman lacak.
2. Pelanggan membuka `lacak.php` dan mengisi **nopol** (`D 1234 GR`, `d1234gr`, `D-1234-GR` semuanya diterima) atau **kode resi** (`SRV-K7M2XQ`, `srv k7m2xq` juga diterima).
3. Halaman menampilkan bar progres (33% / 67% / 100%) dan timeline 3 tahap: **Menunggu, Dikerjakan, Selesai**.
   Timeline mendatar di layar lebar dan menurun di ponsel. Status diambil langsung dari kolom `status` tabel `servis`,
   jadi saat admin mengubah status (tombol **Status** atau **Edit** di menu Servis), halaman pelanggan ikut berubah.
4. Selama servis belum selesai, halaman memeriksa perubahan tiap 30 detik dan **memuat ulang sendiri** begitu status berubah
   (indikator "Live" di bagian bawah). Setelah Selesai, pemeriksaan berhenti. Interval diatur lewat konstanta `LACAK_SELANG` di `lacak.php`.

**Privasi (halaman ini terbuka untuk siapa saja):**

| Dicari dengan | Yang tampil |
|---------------|-------------|
| **Kode resi** (rahasia, hanya ada di nota) | Status dan tanggal, keluhan, nama mekanik, total biaya, status pembayaran |
| **Nopol** (terlihat di kendaraan, jadi tidak rahasia) | Hanya status dan tanggal (maksimal 5 servis terbaru). Nopol disamarkan: `D 12** GR` |

Nama pemilik, telepon, dan alamat **tidak pernah** tampil di halaman publik. Pencarian yang tidak ditemukan dibatasi
**8 kali per 5 menit per sesi browser**, supaya kode resi tidak mudah ditebak. Kode resi tidak berubah saat servis diedit.

**Untuk database yang sudah ada:** kolom `kode_resi` harus ditambahkan sekali (lihat langkah 3 di bawah).
Servis lama otomatis diberi kode resi saat halaman pertama dibuka setelah migrasi.

**Agar bisa dibuka dari HP pelanggan:** alamat `localhost` hanya berlaku di komputer itu sendiri.
Untuk membuka dari perangkat lain di Wi-Fi yang sama, pakai alamat IP komputer (cek lewat `ipconfig`, misalnya `http://192.168.1.10/bengkel-web/lacak.php`).
Alamat yang tercetak di nota mengikuti alamat yang dipakai admin saat mencetak, jadi cetak nota dari alamat IP tersebut.
Windows Firewall mungkin meminta izin untuk Apache.

**Batasan yang perlu diketahui:**

- Hanya **servis** yang bisa dilacak. Restorasi belum.
- Sistem hanya menyimpan status terbaru, bukan riwayat jam per perubahan status. Yang tampil hanya tanggal masuk dan tanggal selesai.
- Pembatasan pencarian berbasis sesi browser (cukup untuk proyek lokal, bisa dilewati dengan menghapus cookie).
  Bila suatu saat dipublikasikan, pindahkan ke pembatasan berbasis IP.
- Menghapus servis terakhir membuat ID-nya dipakai lagi, tetapi kode resinya selalu baru.

## Cara Menjalankan (XAMPP)

1. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder `bengkel-web` ke `C:\xampp\htdocs\`.
3. Siapkan database, pilih salah satu:
   - **Database baru / boleh dikosongkan**: buka `http://localhost/phpmyadmin`, tab **Import**, pilih `database.sql`, klik **Import**.
   - **Sudah punya data versi lama yang ingin dipertahankan**: pilih database `db_bengkel`, tab **SQL**, tempel seluruh isi `migrasi.sql`, klik **Kirim**. Jalankan **sekali saja**. Data lama aman, hanya kolom dan tabel baru yang ditambahkan.
   - **Sudah menjalankan `migrasi.sql` versi sebelumnya (tanpa fitur lacak)**: cukup jalankan `migrasi_resi.sql` dengan cara yang sama, **sekali saja**. Isinya hanya menambah kolom `kode_resi` pada tabel `servis`.
4. Buka `http://localhost/bengkel-web/` di browser.
5. Login dengan akun bawaan (sama dengan program C++):
   - Username: `jawasundagoat`
   - Password: `118129`

Pengaturan koneksi database ada di `koneksi.php` (default XAMPP: host `localhost`, user `root`, tanpa password).

> Jangan membuka lewat VS Code "Go Live" (Live Server). Live Server tidak bisa menjalankan PHP. Pakai alamat XAMPP di atas.

### Muncul halaman "Database belum diperbarui"?

Artinya database `db_bengkel` masih versi lama, sedangkan program ini butuh tabel dan kolom baru
(`mekanik`, `sparepart`, `servis_item`, `status_bayar`, `tanggal_masuk`, `kode_resi`, dll). Halaman itu menyebutkan apa saja yang belum ada.
Selesaikan dengan langkah nomor 3 di atas: `migrasi.sql` (data lama dipertahankan), `migrasi_resi.sql` (jika hanya `kode_resi` yang kurang) atau `database.sql` (database baru),
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
├── migrasi_resi.sql        hanya menambah kolom kode_resi (untuk yang sudah menjalankan migrasi.sql sebelumnya)
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
├── nota.php                nota cetak servis / restorasi (nota servis memuat kode lacak)
├── lacak.php               HALAMAN PUBLIK (tanpa login): lacak progres servis dengan nopol / kode resi
├── partials/
│   ├── header.php          <head>, navbar, dan strip aksen (dipakai semua halaman)
│   └── footer.php          footer, Bootstrap, dan assets/js/app.js
└── assets/
    ├── css/tema-gr.css     seluruh tema tampilan balap (warna, tombol, tabel, form)
    ├── js/app.js           titik ribuan otomatis, isi otomatis form edit, konfirmasi hapus
    ├── js/lacak.js         pembaruan otomatis halaman lacak (cek status tiap 30 detik)
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

- Halaman publik `lacak.php` hanya menerima nopol atau kode resi dengan format ketat (huruf, angka, strip), tidak menampilkan data pribadi, dan membatasi pencarian yang gagal.
- Semua isian form di-escape sebelum masuk query (`dariPost()` di `fungsi.php`), jadi tanda petik seperti `D'Angelo` tidak merusak query.
- Semua teks yang tampil di halaman melewati `h()` (`htmlspecialchars`), jadi `<script>` yang diketik di form hanya tampil sebagai teks.
- Penghapusan memakai tombol **POST** (bukan link `?hapus=`), sehingga tidak bisa terpicu hanya dengan membuka alamat.
- Pilihan status, urutan, dan filter hanya menerima nilai dari daftar yang diizinkan.
- Sel CSV yang diawali `=`, `+`, `-`, atau `@` diberi tanda petik agar tidak dibaca Excel sebagai rumus.

Catatan: query masih berupa teks yang di-escape, belum *prepared statement*. Itu cukup untuk proyek lokal, tetapi bila suatu saat dipublikasikan ke internet, ganti ke prepared statement.
