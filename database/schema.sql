CREATE DATABASE IF NOT EXISTS pdrb_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pdrb_db;

CREATE TABLE IF NOT EXISTS mst_wilayah (
  kode_wilayah   VARCHAR(10)  NOT NULL,
  nama_wilayah   VARCHAR(100) NOT NULL,
  kode_induk     VARCHAR(10)  NULL,
  level_wilayah  ENUM('PROVINSI','KABUPATEN','KOTA') NOT NULL,
  is_active      BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (kode_wilayah),
  KEY idx_wilayah_induk (kode_induk),
  CONSTRAINT fk_wilayah_induk
    FOREIGN KEY (kode_induk) REFERENCES mst_wilayah(kode_wilayah)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB;
