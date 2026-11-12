USE db_bengkel;

ALTER TABLE servis    MODIFY biaya DECIMAL(15, 2) NOT NULL;
ALTER TABLE restorasi MODIFY biaya DECIMAL(15, 2) NOT NULL;

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

ALTER TABLE servis
    ADD COLUMN id_mekanik VARCHAR(50) NULL,
    ADD COLUMN biaya_jasa DECIMAL(15, 2) NOT NULL DEFAULT 0,
    ADD COLUMN status_bayar VARCHAR(20) NOT NULL DEFAULT 'Belum Lunas',
    ADD COLUMN tanggal_masuk DATE NULL,
    ADD COLUMN tanggal_selesai DATE NULL,
    ADD COLUMN kode_resi VARCHAR(20) NULL,
    ADD UNIQUE KEY uq_servis_kode_resi (kode_resi),
    ADD CONSTRAINT fk_servis_mekanik FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE servis SET biaya_jasa = biaya, tanggal_masuk = CURDATE();
UPDATE servis SET tanggal_selesai = CURDATE() WHERE status = 'Selesai';

ALTER TABLE restorasi
    ADD COLUMN id_mekanik VARCHAR(50) NULL,
    ADD COLUMN status_bayar VARCHAR(20) NOT NULL DEFAULT 'Belum Lunas',
    ADD COLUMN tanggal_masuk DATE NULL,
    ADD COLUMN tanggal_selesai DATE NULL,
    ADD CONSTRAINT fk_restorasi_mekanik FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE restorasi SET tanggal_masuk = CURDATE();
UPDATE restorasi SET tanggal_selesai = CURDATE() WHERE status = 'Selesai';

CREATE TABLE IF NOT EXISTS servis_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servis VARCHAR(50) NOT NULL,
    id_sparepart VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_servis) REFERENCES servis(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS penjualan (
    id VARCHAR(50) PRIMARY KEY,
    tanggal DATE NOT NULL,
    nama_pembeli VARCHAR(100) NULL,
    total DECIMAL(15, 2) NOT NULL DEFAULT 0,
    bayar DECIMAL(15, 2) NOT NULL DEFAULT 0,
    kembalian DECIMAL(15, 2) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS penjualan_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan VARCHAR(50) NOT NULL,
    id_sparepart VARCHAR(50) NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_sparepart) REFERENCES sparepart(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
