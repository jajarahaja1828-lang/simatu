SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================
-- DATABASE SIMATU
-- =========================================

CREATE DATABASE IF NOT EXISTS simatu;
USE simatu;

-- =========================================
-- USERS
-- =========================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- BARANG PERSEDIAAN
-- =========================================

CREATE TABLE IF NOT EXISTS `barang_persediaan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(20) NOT NULL,
  `nama_barang` varchar(200) NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,

  -- TAMBAHAN TANGGAL
  `tanggal` date NOT NULL,

  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_barang` (`kode_barang`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- TRANSAKSI MASUK
-- =========================================

CREATE TABLE IF NOT EXISTS `transaksi_masuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barang_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `barang_id` (`barang_id`),

  CONSTRAINT `fk_masuk_barang`
  FOREIGN KEY (`barang_id`)
  REFERENCES `barang_persediaan` (`id`)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- TRANSAKSI KELUAR
-- =========================================

CREATE TABLE IF NOT EXISTS `transaksi_keluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `barang_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `tujuan` varchar(200) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `barang_id` (`barang_id`),

  CONSTRAINT `fk_keluar_barang`
  FOREIGN KEY (`barang_id`)
  REFERENCES `barang_persediaan` (`id`)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- BMN KATEGORI
-- =========================================

CREATE TABLE IF NOT EXISTS `bmn_kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(20) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `jenis` enum('bergerak','tidak_bergerak') NOT NULL DEFAULT 'bergerak',

  PRIMARY KEY (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- BMN ASET
-- =========================================

CREATE TABLE IF NOT EXISTS `bmn_aset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_id` int(11) NOT NULL,
  `kode_aset` varchar(30) NOT NULL,
  `nama_aset` varchar(200) NOT NULL,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') NOT NULL DEFAULT 'Baik',
  `satuan` varchar(20) NOT NULL DEFAULT 'Unit',
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `nilai_perolehan` decimal(15,2) DEFAULT 0,
  `tanggal_perolehan` date DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`),

  CONSTRAINT `fk_aset_kategori`
  FOREIGN KEY (`kategori_id`)
  REFERENCES `bmn_kategori` (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- ANGGARAN
-- =========================================

CREATE TABLE IF NOT EXISTS `anggaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tahun` year(4) NOT NULL,
  `pagu_anggaran` decimal(15,2) NOT NULL DEFAULT 0,
  `realisasi_pegawai` decimal(15,2) NOT NULL DEFAULT 0,
  `realisasi_barang` decimal(15,2) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `tahun` (`tahun`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- PEGAWAI
-- =========================================

CREATE TABLE IF NOT EXISTS `pegawai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nip` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `pangkat` varchar(50) DEFAULT NULL,
  `golongan` enum('I','II','III','IV') NOT NULL DEFAULT 'III',
  `jabatan` varchar(100) DEFAULT NULL,
  `unit_kerja` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT 'L',
  `status` enum('Aktif','Pensiun') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `nip` (`nip`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- KENAIKAN PANGKAT
-- =========================================

CREATE TABLE IF NOT EXISTS `kenaikan_pangkat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) NOT NULL,
  `pangkat_lama` varchar(50) DEFAULT NULL,
  `pangkat_baru` varchar(50) NOT NULL,
  `golongan_lama` varchar(10) DEFAULT NULL,
  `golongan_baru` varchar(10) NOT NULL,
  `tanggal_efektif` date NOT NULL,
  `no_sk` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `pegawai_id` (`pegawai_id`),

  CONSTRAINT `fk_kp_pegawai`
  FOREIGN KEY (`pegawai_id`)
  REFERENCES `pegawai` (`id`)
  ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- DEFAULT ADMIN
-- username : admin
-- password : password
-- =========================================

INSERT IGNORE INTO `users`
(`full_name`, `email`, `username`, `password`, `role`)
VALUES
(
'Administrator',
'admin@bapas.go.id',
'admin',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'admin'
);

-- =========================================
-- DATA BARANG
-- =========================================

INSERT IGNORE INTO `barang_persediaan`
(`kode_barang`, `nama_barang`, `satuan`, `stok`, `tanggal`)
VALUES
('BP-001', 'Kertas HVS A4 80gr', 'Rim', 45, '2026-05-16'),
('BP-002', 'Tinta Printer Hitam', 'Botol', 12, '2026-05-16'),
('BP-003', 'Ballpoint Pilot', 'Lusin', 3, '2026-05-16'),
('BP-004', 'Map Plastik', 'Lembar', 100, '2026-05-16'),
('BP-005', 'Staples No.10', 'Kotak', 20, '2026-05-16'),
('BP-006', 'Amplop Coklat Besar', 'Lembar', 150, '2026-05-16'),
('BP-007', 'Spidol Whiteboard', 'Buah', 10, '2026-05-16'),
('BP-008', 'Kertas Folio', 'Rim', 15, '2026-05-16');

-- =========================================
-- DATA TRANSAKSI MASUK
-- =========================================

INSERT IGNORE INTO `transaksi_masuk`
(`barang_id`, `jumlah`, `tanggal`, `keterangan`, `created_by`)
VALUES
(1, 50, '2026-01-10', 'Pengadaan awal tahun', 1),
(2, 20, '2026-01-10', 'Pengadaan awal tahun', 1),
(3, 10, '2026-02-15', 'Pembelian ATK', 1);

-- =========================================
-- DATA TRANSAKSI KELUAR
-- =========================================

INSERT IGNORE INTO `transaksi_keluar`
(`barang_id`, `jumlah`, `tanggal`, `tujuan`, `keterangan`, `created_by`)
VALUES
(1, 15, '2026-01-15', 'Seksi Bimbingan Klien', 'Kebutuhan operasional', 1),
(2, 5, '2026-01-20', 'Bidang Administrasi', 'Penggantian tinta', 1);