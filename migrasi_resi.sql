-- =====================================================
-- MIGRASI KODE RESI (fitur Lacak servis / lacak.php)
--
-- Untuk database db_bengkel yang SUDAH menjalankan migrasi.sql atau database.sql
-- versi sebelumnya (tabel mekanik, sparepart, dan servis_item sudah ada).
-- Database yang paling lama (belum ada tabel mekanik) cukup menjalankan migrasi.sql,
-- karena file itu sudah memuat kolom ini.
--
-- Cara pakai: phpMyAdmin -> pilih database db_bengkel -> tab SQL -> tempel
-- seluruh isi file ini -> klik Kirim/Go. Jalankan SEKALI saja.
--
-- Kode resi untuk servis lama diisi otomatis oleh aplikasi saat halaman
-- pertama dibuka setelah migrasi (tidak perlu diisi manual).
-- =====================================================

USE db_bengkel;

ALTER TABLE servis
    ADD COLUMN kode_resi VARCHAR(20) NULL,
    ADD UNIQUE KEY uq_servis_kode_resi (kode_resi);
