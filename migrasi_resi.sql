USE db_bengkel;

ALTER TABLE servis
    ADD COLUMN kode_resi VARCHAR(20) NULL,
    ADD UNIQUE KEY uq_servis_kode_resi (kode_resi);
