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

CREATE TABLE IF NOT EXISTS servis (
    id VARCHAR(50) PRIMARY KEY,
    id_kendaraan VARCHAR(50) NOT NULL,
    keluhan TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    biaya DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_kendaraan) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS restorasi (
    id VARCHAR(50) PRIMARY KEY,
    id_kendaraan VARCHAR(50) NOT NULL,
    deskripsi TEXT NOT NULL,
    status VARCHAR(50) NOT NULL,
    biaya DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_kendaraan) REFERENCES kendaraan(id) ON DELETE CASCADE ON UPDATE CASCADE
);