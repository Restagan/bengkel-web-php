CREATE DATABASE IF NOT EXISTS db_bengkel;
USE db_bengkel;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);

INSERT INTO admin (username, password)
SELECT 'jawasundagoat', '118129' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admin WHERE username = 'jawasundagoat');

CREATE TABLE IF NOT EXISTS pelanggan (
    id VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telp VARCHAR(20) NOT NULL,
    alamat TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS kendaraan (
    id VARCHAR(50) PRIMARY KEY,
    id_pelanggan VARCHAR(50) NOT NULL,
    merk VARCHAR(100) NOT NULL,
    nopol VARCHAR(20) NOT NULL,
    tahun VARCHAR(4) NOT NULL,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS mekanik (
    id VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telp VARCHAR(20) NOT NULL,
    spesialis VARCHAR(100) NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS sparepart (
    id VARCHAR(50) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'pcs',
    harga DECIMAL(15, 2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    stok_minimum INT NOT NULL DEFAULT 5
);

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
    FOREIGN KEY (id_kendaraan) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS servis_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servis VARCHAR(50) NOT NULL,
    id_sparepart VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_servis) REFERENCES servis(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

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
