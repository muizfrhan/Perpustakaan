-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for pusaku
CREATE DATABASE IF NOT EXISTS `pusaku` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `pusaku`;

-- Dumping structure for table pusaku.anggota
CREATE TABLE IF NOT EXISTS `anggota` (
  `nim` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-Laki','Perempuan') NOT NULL,
  `jurusan` varchar(40) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `status_mhs` enum('Aktif','Tidak Aktif') NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `password` varchar(50) DEFAULT 'mhsudb123',
  PRIMARY KEY (`nim`)
) ENGINE=InnoDB AUTO_INCREMENT=230103422 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table pusaku.buku
CREATE TABLE IF NOT EXISTS `buku` (
  `cover` varchar(255) NOT NULL,
  `kode_buku` varchar(10) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `judul_buku` varchar(100) NOT NULL,
  `pengarang` varchar(100) NOT NULL,
  `penerbit` varchar(100) NOT NULL,
  `tanggal_terbit` date NOT NULL,
  `jumlah_halaman` int NOT NULL,
  `bahasa` varchar(20) NOT NULL,
  `deskripsi_buku` text NOT NULL,
  `stok` int DEFAULT NULL,
  `status` enum('Tersedia','Dipinjam','Kosong') NOT NULL DEFAULT 'Tersedia',
  PRIMARY KEY (`kode_buku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table pusaku.detail_peminjaman
CREATE TABLE IF NOT EXISTS `detail_peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_pinjam` varchar(6) DEFAULT NULL,
  `kode_buku` varchar(6) DEFAULT NULL,
  `kondisi_buku_pinjam` enum('bagus','rusak') NOT NULL DEFAULT 'bagus',
  PRIMARY KEY (`id`),
  KEY `kode_pinjam` (`kode_pinjam`),
  KEY `kode_buku` (`kode_buku`),
  CONSTRAINT `detail_peminjaman_ibfk_1` FOREIGN KEY (`kode_pinjam`) REFERENCES `peminjaman` (`kode_pinjam`) ON DELETE CASCADE,
  CONSTRAINT `detail_peminjaman_ibfk_2` FOREIGN KEY (`kode_buku`) REFERENCES `buku` (`kode_buku`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table pusaku.owner
CREATE TABLE IF NOT EXISTS `owner` (
  `id_owner` int NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL,
  `password` varchar(60) NOT NULL,
  `nama_pemilik` varchar(100) NOT NULL,
  `profil_gambar` varchar(255) NOT NULL,
  PRIMARY KEY (`id_owner`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table pusaku.peminjaman
CREATE TABLE IF NOT EXISTS `peminjaman` (
  `kode_pinjam` varchar(6) NOT NULL,
  `nim` int DEFAULT NULL,
  `id_petugas` int DEFAULT NULL,
  `kode_buku` varchar(10) DEFAULT NULL,
  `tgl_pinjam` datetime DEFAULT CURRENT_TIMESTAMP,
  `estimasi_pinjam` datetime DEFAULT NULL,
  `kondisi_buku_pinjam` enum('Bagus','Rusak') NOT NULL DEFAULT 'Bagus',
  `status` enum('Dipinjam','Dikembalikan') NOT NULL DEFAULT 'Dipinjam',
  PRIMARY KEY (`kode_pinjam`),
  UNIQUE KEY `kode_pinjam` (`kode_pinjam`),
  KEY `nim` (`nim`),
  KEY `id_petugas` (`id_petugas`),
  KEY `kode_buku` (`kode_buku`),
  CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`nim`) REFERENCES `anggota` (`nim`) ON DELETE CASCADE,
  CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_petugas`) REFERENCES `petugas` (`id_petugas`) ON DELETE CASCADE,
  CONSTRAINT `peminjaman_ibfk_3` FOREIGN KEY (`kode_buku`) REFERENCES `buku` (`kode_buku`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table pusaku.pengembalian
CREATE TABLE IF NOT EXISTS `pengembalian` (
  `kode_kembali` varchar(6) DEFAULT NULL,
  `tgl_kembali` datetime DEFAULT NULL,
  `kode_pinjam` varchar(6) DEFAULT NULL,
  `kondisi_buku` enum('Bagus','Rusak','Hilang') NOT NULL DEFAULT 'Bagus',
  `denda` double(10,2) DEFAULT NULL,
  `status` enum('Lunas','Belum Lunas') DEFAULT NULL,
  `pembayaran` enum('Tidak Ada','Kes','Transfer') DEFAULT NULL,
  KEY `kode_pinjam` (`kode_pinjam`),
  CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`kode_pinjam`) REFERENCES `peminjaman` (`kode_pinjam`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for table pusaku.petugas
CREATE TABLE IF NOT EXISTS `petugas` (
  `id_petugas` int NOT NULL AUTO_INCREMENT,
  `nama_petugas` varchar(100) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(60) NOT NULL,
  `jenis_kelamin` enum('Laki-Laki','Perempuan') NOT NULL,
  `no_telp` char(15) NOT NULL,
  `profil_gambar` varchar(255) NOT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  PRIMARY KEY (`id_petugas`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Data exporting was unselected.

-- Dumping structure for trigger pusaku.update_buku_status
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_buku_status` BEFORE UPDATE ON `buku` FOR EACH ROW BEGIN
    IF NEW.stok = -1 THEN
        SET NEW.status = 'Kosong';
    ELSEIF NEW.stok = 0 THEN
        SET NEW.status = 'Dipinjam';
    ELSEIF NEW.stok > 1 THEN
        SET NEW.status = 'Tersedia';
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Dumping structure for trigger pusaku.update_status_after_return
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `update_status_after_return` AFTER INSERT ON `pengembalian` FOR EACH ROW BEGIN
    UPDATE peminjaman
    SET status = 'Dikembalikan'
    WHERE kode_pinjam = NEW.kode_pinjam;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
