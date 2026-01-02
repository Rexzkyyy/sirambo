-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Jan 2026 pada 01.46
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sirambo`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `fact_pdrb_fenomena`
--

CREATE TABLE `fact_pdrb_fenomena` (
  `id_fenomena` bigint(20) NOT NULL,
  `id_transaksi` bigint(20) NOT NULL,
  `kode_wilayah` varchar(10) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `id_komponen` int(11) NOT NULL,
  `deskripsi_fenomena` text NOT NULL,
  `kategori_fenomena` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `fact_pdrb_transaksi`
--

CREATE TABLE `fact_pdrb_transaksi` (
  `id_transaksi` bigint(20) NOT NULL,
  `kode_wilayah` varchar(10) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `id_komponen` int(11) NOT NULL,
  `nilai_adhb` decimal(20,2) NOT NULL DEFAULT 0.00,
  `nilai_adhk` decimal(20,2) NOT NULL DEFAULT 0.00,
  `status_data` enum('INPUT','RECONCILED','FINAL') NOT NULL DEFAULT 'INPUT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_audit_data`
--

CREATE TABLE `log_audit_data` (
  `id_log` bigint(20) NOT NULL,
  `id_transaksi` bigint(20) NOT NULL,
  `tipe_aksi` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `kolom_berubah` varchar(50) DEFAULT NULL,
  `nilai_lama` decimal(20,2) DEFAULT NULL,
  `nilai_baru` decimal(20,2) DEFAULT NULL,
  `alasan_perubahan` varchar(255) DEFAULT NULL,
  `user_id` varchar(50) NOT NULL,
  `waktu_aksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `fase_rekonsiliasi` enum('MANDIRI','PRA-REKON','REKON-PROV') NOT NULL DEFAULT 'MANDIRI'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mst_wilayah`
--

CREATE TABLE `mst_wilayah` (
  `kode_wilayah` varchar(10) NOT NULL,
  `nama_wilayah` varchar(100) NOT NULL,
  `kode_induk` varchar(10) DEFAULT NULL,
  `level_wilayah` enum('PROVINSI','KABUPATEN','KOTA') NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mst_wilayah`
--

INSERT INTO `mst_wilayah` (`kode_wilayah`, `nama_wilayah`, `kode_induk`, `level_wilayah`, `is_active`) VALUES
('09888888', 'kendari', NULL, 'KABUPATEN', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_komponen_pdrb`
--

CREATE TABLE `ref_komponen_pdrb` (
  `id_komponen` int(11) NOT NULL,
  `kode_publikasi` varchar(20) DEFAULT NULL,
  `nama_komponen` varchar(255) NOT NULL,
  `id_induk` int(11) DEFAULT NULL,
  `jenis_pendekatan` enum('LAPANGAN_USAHA','PENGELUARAN') NOT NULL,
  `level_hierarki` int(11) NOT NULL,
  `is_calculable` tinyint(1) NOT NULL DEFAULT 0,
  `id_versi_kbli` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_periode_laporan`
--

CREATE TABLE `ref_periode_laporan` (
  `id_periode` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `triwulan` tinyint(4) NOT NULL,
  `status_input` enum('OPEN','LOCKED') NOT NULL DEFAULT 'OPEN',
  `tenggat_waktu` datetime DEFAULT NULL,
  `deskripsi` varchar(100) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ref_versi_statistik`
--

CREATE TABLE `ref_versi_statistik` (
  `id_versi` int(11) NOT NULL,
  `nama_versi` varchar(50) NOT NULL,
  `tahun_dasar` year(4) NOT NULL,
  `berlaku_mulai` date NOT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `status_input_wilayah`
--

CREATE TABLE `status_input_wilayah` (
  `id_status` bigint(20) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `kode_wilayah` varchar(10) NOT NULL,
  `persentase_selesai` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status_finalisasi` enum('DRAFT','SUBMITTED','APPROVED') NOT NULL DEFAULT 'DRAFT',
  `waktu_submit` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `fact_pdrb_fenomena`
--
ALTER TABLE `fact_pdrb_fenomena`
  ADD PRIMARY KEY (`id_fenomena`),
  ADD KEY `idx_fenomena_transaksi` (`id_transaksi`),
  ADD KEY `idx_fenomena_wilayah` (`kode_wilayah`),
  ADD KEY `idx_fenomena_periode` (`id_periode`),
  ADD KEY `idx_fenomena_komponen` (`id_komponen`);

--
-- Indeks untuk tabel `fact_pdrb_transaksi`
--
ALTER TABLE `fact_pdrb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD UNIQUE KEY `uq_transaksi` (`kode_wilayah`,`id_periode`,`id_komponen`),
  ADD KEY `idx_transaksi_wilayah` (`kode_wilayah`),
  ADD KEY `idx_transaksi_periode` (`id_periode`),
  ADD KEY `idx_transaksi_komponen` (`id_komponen`),
  ADD KEY `idx_transaksi_status` (`status_data`);

--
-- Indeks untuk tabel `log_audit_data`
--
ALTER TABLE `log_audit_data`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_audit_transaksi` (`id_transaksi`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_waktu` (`waktu_aksi`);

--
-- Indeks untuk tabel `mst_wilayah`
--
ALTER TABLE `mst_wilayah`
  ADD PRIMARY KEY (`kode_wilayah`),
  ADD KEY `idx_wilayah_induk` (`kode_induk`),
  ADD KEY `idx_wilayah_level` (`level_wilayah`);

--
-- Indeks untuk tabel `ref_komponen_pdrb`
--
ALTER TABLE `ref_komponen_pdrb`
  ADD PRIMARY KEY (`id_komponen`),
  ADD KEY `idx_komponen_induk` (`id_induk`),
  ADD KEY `idx_komponen_pendekatan` (`jenis_pendekatan`),
  ADD KEY `idx_komponen_versi` (`id_versi_kbli`);

--
-- Indeks untuk tabel `ref_periode_laporan`
--
ALTER TABLE `ref_periode_laporan`
  ADD PRIMARY KEY (`id_periode`),
  ADD UNIQUE KEY `uq_periode` (`tahun`,`triwulan`),
  ADD KEY `idx_periode_status` (`status_input`);

--
-- Indeks untuk tabel `ref_versi_statistik`
--
ALTER TABLE `ref_versi_statistik`
  ADD PRIMARY KEY (`id_versi`),
  ADD KEY `idx_versi_aktif` (`status_aktif`),
  ADD KEY `idx_versi_periode` (`berlaku_mulai`,`berlaku_sampai`);

--
-- Indeks untuk tabel `status_input_wilayah`
--
ALTER TABLE `status_input_wilayah`
  ADD PRIMARY KEY (`id_status`),
  ADD UNIQUE KEY `uq_status_wilayah_periode` (`id_periode`,`kode_wilayah`),
  ADD KEY `idx_status_periode` (`id_periode`),
  ADD KEY `idx_status_wilayah` (`kode_wilayah`),
  ADD KEY `idx_status_finalisasi` (`status_finalisasi`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `fact_pdrb_fenomena`
--
ALTER TABLE `fact_pdrb_fenomena`
  MODIFY `id_fenomena` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `fact_pdrb_transaksi`
--
ALTER TABLE `fact_pdrb_transaksi`
  MODIFY `id_transaksi` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `log_audit_data`
--
ALTER TABLE `log_audit_data`
  MODIFY `id_log` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_komponen_pdrb`
--
ALTER TABLE `ref_komponen_pdrb`
  MODIFY `id_komponen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_periode_laporan`
--
ALTER TABLE `ref_periode_laporan`
  MODIFY `id_periode` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `ref_versi_statistik`
--
ALTER TABLE `ref_versi_statistik`
  MODIFY `id_versi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `status_input_wilayah`
--
ALTER TABLE `status_input_wilayah`
  MODIFY `id_status` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `fact_pdrb_fenomena`
--
ALTER TABLE `fact_pdrb_fenomena`
  ADD CONSTRAINT `fk_fenomena_komponen` FOREIGN KEY (`id_komponen`) REFERENCES `ref_komponen_pdrb` (`id_komponen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fenomena_periode` FOREIGN KEY (`id_periode`) REFERENCES `ref_periode_laporan` (`id_periode`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fenomena_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `fact_pdrb_transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_fenomena_wilayah` FOREIGN KEY (`kode_wilayah`) REFERENCES `mst_wilayah` (`kode_wilayah`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `fact_pdrb_transaksi`
--
ALTER TABLE `fact_pdrb_transaksi`
  ADD CONSTRAINT `fk_transaksi_komponen` FOREIGN KEY (`id_komponen`) REFERENCES `ref_komponen_pdrb` (`id_komponen`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_periode` FOREIGN KEY (`id_periode`) REFERENCES `ref_periode_laporan` (`id_periode`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_wilayah` FOREIGN KEY (`kode_wilayah`) REFERENCES `mst_wilayah` (`kode_wilayah`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `log_audit_data`
--
ALTER TABLE `log_audit_data`
  ADD CONSTRAINT `fk_audit_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `fact_pdrb_transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mst_wilayah`
--
ALTER TABLE `mst_wilayah`
  ADD CONSTRAINT `fk_wilayah_induk` FOREIGN KEY (`kode_induk`) REFERENCES `mst_wilayah` (`kode_wilayah`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ref_komponen_pdrb`
--
ALTER TABLE `ref_komponen_pdrb`
  ADD CONSTRAINT `fk_komponen_induk` FOREIGN KEY (`id_induk`) REFERENCES `ref_komponen_pdrb` (`id_komponen`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_komponen_versi` FOREIGN KEY (`id_versi_kbli`) REFERENCES `ref_versi_statistik` (`id_versi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `status_input_wilayah`
--
ALTER TABLE `status_input_wilayah`
  ADD CONSTRAINT `fk_status_periode` FOREIGN KEY (`id_periode`) REFERENCES `ref_periode_laporan` (`id_periode`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_status_wilayah` FOREIGN KEY (`kode_wilayah`) REFERENCES `mst_wilayah` (`kode_wilayah`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
