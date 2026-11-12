-- =====================================================
-- MIGRASI: untuk database db_bengkel yang SUDAH berisi data versi lama
-- (data pelanggan, kendaraan, servis, dan restorasi tetap aman).
--
-- Cara pakai: phpMyAdmin -> pilih database db_bengkel -> tab SQL -> tempel
-- seluruh isi file ini -> klik Kirim/Go.
--
-- PENTING: jalankan SEKALI saja. Menjalankan dua kali akan error
-- "Duplicate column name" (data tidak rusak, tetapi tidak ada gunanya).
-- Jika database masih kosong, pakai database.sql saja.
-- =====================================================

USE db_bengkel;

-- 1. Perbesar kolom biaya (maksimal sebelumnya sekitar Rp 100 juta)
ALTER TABLE servis    MODIFY biaya DECIMAL(15, 2) NOT NULL;
ALTER TABLE restorasi MODIFY biaya DECIMAL(15, 2) NOT NULL;

-- 2. Tabel baru: mekanik dan sparepart
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

-- 3. Kolom baru pada servis: mekanik, biaya jasa, status bayar, tanggal
ALTER TABLE servis
    ADD COLUMN id_mekanik VARCHAR(50) NULL,
    ADD COLUMN biaya_jasa DECIMAL(15, 2) NOT NULL DEFAULT 0,
    ADD COLUMN status_bayar VARCHAR(20) NOT NULL DEFAULT 'Belum Lunas',
    ADD COLUMN tanggal_masuk DATE NULL,
    ADD COLUMN tanggal_selesai DATE NULL,
    ADD COLUMN kode_resi VARCHAR(20) NULL,
    ADD UNIQUE KEY uq_servis_kode_resi (kode_resi),
    ADD CONSTRAINT fk_servis_mekanik FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Data servis lama: seluruh biaya lama dianggap biaya jasa, tanggal masuk diisi hari ini
UPDATE servis SET biaya_jasa = biaya, tanggal_masuk = CURDATE();
UPDATE servis SET tanggal_selesai = CURDATE() WHERE status = 'Selesai';

-- 4. Kolom baru pada restorasi
ALTER TABLE restorasi
    ADD COLUMN id_mekanik VARCHAR(50) NULL,
    ADD COLUMN status_bayar VARCHAR(20) NOT NULL DEFAULT 'Belum Lunas',
    ADD COLUMN tanggal_masuk DATE NULL,
    ADD COLUMN tanggal_selesai DATE NULL,
    ADD CONSTRAINT fk_restorasi_mekanik FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE restorasi SET tanggal_masuk = CURDATE();
UPDATE restorasi SET tanggal_selesai = CURDATE() WHERE status = 'Selesai';

-- 5. Tabel suku cadang yang dipakai pada servis
CREATE TABLE IF NOT EXISTS servis_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servis VARCHAR(50) NOT NULL,
    id_sparepart VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_servis) REFERENCES servis(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
