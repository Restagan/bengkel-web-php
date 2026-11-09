# Sistem Manajemen Bengkel (Web)

Versi web dari program C++ "Tugas Kelompok Bengkel". Dibuat dengan PHP (mysqli), MySQL/MariaDB, dan Bootstrap 5.
Proyek ini hanya untuk dijalankan secara lokal.

## Fitur

| Modul     | Fitur                                                                 |
|-----------|-----------------------------------------------------------------------|
| Login     | Login admin (tabel `admin`), logout                                   |
| Dashboard | Total pelanggan, total kendaraan, total pendapatan (servis + restorasi) |
| Pelanggan | Tambah, lihat, cari (ID/nama), urutkan ID, hapus                      |
| Kendaraan | Tambah (pilih pemilik), lihat, cari, urutkan ID, hapus                |
| Servis    | Tambah, lihat, cari, urutkan biaya, ubah status, hapus, total pendapatan |
| Restorasi | Tambah, lihat, cari, urutkan biaya, ubah status, hapus, total pendapatan |

## Cara Menjalankan (XAMPP)

1. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder `bengkel-web` ke `C:\xampp\htdocs\`.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih file `database.sql`, lalu klik **Import**.
   Database `db_bengkel` beserta semua tabel dan akun admin akan dibuat otomatis.
4. Buka `http://localhost/bengkel-web/` di browser.
5. Login dengan akun bawaan (sama dengan program C++):
   - Username: `jawasundagoat`
   - Password: `118129`

Pengaturan koneksi database ada di `koneksi.php` (default XAMPP: host `localhost`, user `root`, tanpa password).

## Struktur File

```
bengkel-web/
├── database.sql    skema database + akun admin
├── koneksi.php     koneksi MySQL
├── index.php       halaman login
├── logout.php      keluar
├── dashboard.php   ringkasan data
├── pelanggan.php   modul pelanggan
├── kendaraan.php   modul kendaraan
├── servis.php      modul servis
└── restorasi.php   modul restorasi
```

## Catatan Penting: Penghapusan Berantai (CASCADE)

Relasi antar tabel memakai `FOREIGN KEY ... ON DELETE CASCADE`:

- Menghapus **pelanggan** ikut menghapus semua **kendaraan** miliknya.
- Menghapus **kendaraan** ikut menghapus semua **servis** dan **restorasi** miliknya.

Akibatnya total pendapatan di dashboard ikut berkurang. Ini sengaja, supaya tidak ada data yatim.
Perilaku ini berbeda dengan program C++ yang hanya menghapus satu baris data.
Karena itu tombol Hapus di halaman Pelanggan dan Kendaraan menampilkan peringatan sebelum menghapus.

## Jika Database Sudah Pernah Dibuat dengan Versi Lama

Kolom `biaya` versi lama bertipe `DECIMAL(10,2)` (maksimal sekitar Rp 100 juta).
Perbesar dengan menjalankan SQL ini di phpMyAdmin (tab SQL, database `db_bengkel`):

```sql
ALTER TABLE servis    MODIFY biaya DECIMAL(15,2) NOT NULL;
ALTER TABLE restorasi MODIFY biaya DECIMAL(15,2) NOT NULL;
```

Data yang sudah ada tidak berubah.
