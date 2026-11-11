-- =====================================================
-- Database Sistem Manajemen Bengkel (versi lengkap, untuk database BARU)
--
-- Cara pakai: phpMyAdmin -> tab Import -> pilih file ini.
-- Jika db_bengkel sudah berisi data versi lama dan ingin dipertahankan,
-- JANGAN import file ini, pakai migrasi.sql sebagai gantinya.
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_bengkel;
USE db_bengkel;

-- 1. Tabel Admin (Untuk mengganti login hardcode di C++)
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Akun admin default sesuai program sebelumnya
-- (hanya dimasukkan jika belum ada, jadi aman diimport berulang)
INSERT INTO admin (username, password)
SELECT 'jawasundagoat', '118129' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admin WHERE username = 'jawasundagoat');

-- 2. Tabel Pelanggan
CREATE TABLE IF NOT EXISTS pelanggan (
    id VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telp VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL
);

-- 3. Tabel Kendaraan
-- Menghapus pelanggan ikut menghapus kendaraannya (CASCADE)
CREATE TABLE IF NOT EXISTS kendaraan (
    id VARCHAR(50) PRIMARY KEY,
    id_pelanggan VARCHAR(50) NOT NULL,
    merk VARCHAR(100) NOT NULL,
    nopol VARCHAR(20) NOT NULL,
    tahun VARCHAR(4) NOT NULL,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 4. Tabel Mekanik (baru)
CREATE TABLE IF NOT EXISTS mekanik (
    id VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telp VARCHAR(20) NOT NULL,
    spesialis VARCHAR(100) NOT NULL DEFAULT ''
);

-- 5. Tabel Sparepart / stok suku cadang (baru)
CREATE TABLE IF NOT EXISTS sparepart (
    id VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'pcs',
    harga DECIMAL(15, 2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    stok_minimum INT NOT NULL DEFAULT 5
);

-- 6. Tabel Servis
-- biaya = biaya_jasa + total suku cadang (dihitung ulang otomatis oleh program)
-- Menghapus kendaraan ikut menghapus riwayat servisnya (CASCADE)
-- Menghapus mekanik tidak menghapus servis, kolom id_mekanik menjadi kosong (SET NULL)
CREATE TABLE IF NOT EXISTS servis (
    id VARCHAR(50) PRIMARY KEY,
    id_kendaraan VARCHAR(50) NOT NULL,
    id_mekanik VARCHAR(50) NULL,
    keluhan TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    biaya_jasa DECIMAL(15, 2) NOT NULL DEFAULT 0,
    biaya DECIMAL(15, 2) NOT NULL,
    status_bayar VARCHAR(20) NOT NULL DEFAULT 'Belum Lunas',
    tanggal_masuk DATE NULL,
    tanggal_selesai DATE NULL,
    kode_resi VARCHAR(20) NULL UNIQUE,
    FOREIGN KEY (id_kendaraan) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE
);
-- kode_resi (contoh SRV-K7M2XQ) dibuat otomatis oleh aplikasi untuk fitur Lacak servis (lacak.php).
-- UNIQUE: tidak boleh ada dua servis dengan kode yang sama (nilai kosong/NULL boleh banyak).

-- 7. Tabel Suku cadang yang dipakai pada servis (baru)
-- harga_satuan disimpan saat dipakai, jadi nota lama tidak berubah jika harga sparepart naik
-- Sparepart yang pernah dipakai tidak bisa dihapus (RESTRICT)
CREATE TABLE IF NOT EXISTS servis_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servis VARCHAR(50) NOT NULL,
    id_sparepart VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_servis) REFERENCES servis(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 8. Tabel Restorasi
-- Menghapus kendaraan ikut menghapus riwayat restorasinya (CASCADE)
CREATE TABLE IF NOT EXISTS restorasi (
    id VARCHAR(50) PRIMARY KEY,
    id_kendaraan VARCHAR(50) NOT NULL,
    id_mekanik VARCHAR(50) NULL,
    deskripsi TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    biaya DECIMAL(15, 2) NOT NULL,
    status_bayar VARCHAR(20) NOT NULL DEFAULT 'Belum Lunas',
    tanggal_masuk DATE NULL,
    tanggal_selesai DATE NULL,
    FOREIGN KEY (id_kendaraan) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE
);
