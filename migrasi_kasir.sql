USE db_bengkel;

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
