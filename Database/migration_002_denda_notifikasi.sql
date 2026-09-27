-- ============================================================================
-- MIGRASI 002 - Denda Otomatis, Pengingat Tenggat, Audit & Notifikasi
-- Target  : MySQL 8.0 / MariaDB 10.4 (XAMPP)
-- Prinsip : idempoten (aman dijalankan berulang kali), tidak destruktif
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. TABEL ATURAN DENDA
--    Mengganti magic number di controller (Rp5.000 / Rp20.000 / Rp50.000)
--    menjadi data yang bisa dikelola admin dan diaudit.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `aturan_denda` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_aturan`   VARCHAR(40)  NOT NULL,
  `nama_aturan`   VARCHAR(100) NOT NULL,
  `tipe`          ENUM('PerHari','Tetap','PersenNilaiBuku') NOT NULL DEFAULT 'PerHari',
  `nominal`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `grace_hari`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `hari_maks`     SMALLINT UNSIGNED NULL COMMENT 'NULL = tanpa batas atas',
  `tipe_buku`     ENUM('Fisik','Ebook','Semua') NOT NULL DEFAULT 'Semua',
  `deskripsi`     VARCHAR(255) NULL,
  `aktif`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aturan_kode` (`kode_aturan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed tarif lama sebagai default, agar perilaku sistem tidak berubah mendadak.
INSERT INTO `aturan_denda`
  (`kode_aturan`, `nama_aturan`, `tipe`, `nominal`, `grace_hari`, `hari_maks`, `deskripsi`)
VALUES
  ('TERLAMBAT',   'Denda Keterlambatan',        'PerHari',            5000.00, 0,  60, 'Denda harian keterlambatan pengembalian. Maksimal 60 hari agar tidak memberi beban berlebihan ke mahasiswa.'),
  ('RUSAK',       'Denda Buku Rusak',          'Tetap',             20000.00, 0, NULL, 'Denda tetap untuk buku kembali dalam kondisi rusak.'),
  ('HILANG',      'Denda Buku Hilang',         'Tetap',             50000.00, 0, NULL, 'Denda tetap untuk buku tidak dikembalikan (hilang).'),
  ('PERPANJANGAN','Denda Perpanjangan',        'PerHari',            5000.00, 0,   7, 'Biaya layanan perpanjangan masa pinjam, maksimal 7 hari.')
ON DUPLICATE KEY UPDATE `nama_aturan` = VALUES(`nama_aturan`);

-- ---------------------------------------------------------------------------
-- 2. TABEL NOTIFIKASI
--    dedup_key UNIQUE => idempotent. Cron boleh jalan tiap 5 menit tanpa
--    mengirim reminder yang sama berulang-ulang.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `penerima_type` ENUM('Anggota','Petugas','Owner') NOT NULL,
  `penerima_id`   INT UNSIGNED NOT NULL,
  `judul`         VARCHAR(150) NOT NULL,
  `pesan`         TEXT NOT NULL,
  `tipe`          ENUM('Info','Peringatan','Kritis') NOT NULL DEFAULT 'Info',
  `kategori`      VARCHAR(40) NULL COMMENT 'Tenggat | Terlambat | Denda | Info',
  `ref_table`     VARCHAR(40) NULL,
  `ref_id`        VARCHAR(20) NULL,
  `kanal`         ENUM('InApp','Email','WhatsApp') NOT NULL DEFAULT 'InApp',
  `dibaca`        TINYINT(1) NOT NULL DEFAULT 0,
  `dibaca_at`     DATETIME NULL,
  `dedup_key`     VARCHAR(140) NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notif_dedup` (`dedup_key`),
  KEY `idx_notif_penerima` (`penerima_type`, `penerima_id`, `dibaca`, `created_at`),
  KEY `idx_notif_kategori` (`kategori`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. AUDIT LOG
--    Menjawab: siapa, kapan, dari IP mana, mengubah apa.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_type`  VARCHAR(20)  NOT NULL,
  `actor_id`    INT UNSIGNED NULL,
  `aksi`        VARCHAR(60)  NOT NULL,
  `ref_table`   VARCHAR(40)  NULL,
  `ref_id`      VARCHAR(40)  NULL,
  `detail`      TEXT NULL,
  `ip_address`  VARCHAR(45)  NULL,
  `user_agent`  VARCHAR(255) NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_ref` (`ref_table`, `ref_id`),
  KEY `idx_audit_actor` (`actor_type`, `actor_id`, `created_at`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Riwayat eksekusi job terjadwal (untuk monitoring cron).
CREATE TABLE IF NOT EXISTS `job_log` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_job`     VARCHAR(60) NOT NULL,
  `status`       ENUM('Sukses','Gagal') NOT NULL,
  `diproses`     INT NOT NULL DEFAULT 0,
  `pesan`        VARCHAR(255) NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_job_created` (`nama_job`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3b. SEQUENCE
--     Nomor transaksi tidak boleh dibuat dengan pola "SELECT MAX + 1" karena
--     dua librarian yang memproses pengembalian pada detik yang sama akan
--     mendapat kode yang sama. Pola ini diinisialisasi secara atomik.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sequence` (
  `nama`     VARCHAR(40) NOT NULL,
  `next_val` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sequence` (`nama`, `next_val`) VALUES
  ('kode_pinjam', 1),
  ('kode_kembali', 1)
ON DUPLICATE KEY UPDATE `nama` = `nama`;

-- ---------------------------------------------------------------------------
-- 4. PERBAIKAN INTEGRITAS - peminjaman
-- ---------------------------------------------------------------------------

-- Rincian denda per hari, jumlah hari keterlambatan, dan jejak audit.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'peminjaman'
                 AND COLUMN_NAME = 'hari_terlambat') = 0,
  'ALTER TABLE `peminjaman`
     ADD COLUMN `hari_terlambat`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
     ADD COLUMN `denda_terlambat`  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
     ADD COLUMN `denda_kondisi`    DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
     ADD COLUMN `tgl_kembali_aktual` DATE         NULL,
     ADD COLUMN `perpanjangan_ke`   TINYINT UNSIGNED NOT NULL DEFAULT 0',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------------
-- 5. PERBAIKAN INTEGRITAS - pengembalian
-- ---------------------------------------------------------------------------

-- 5a. Uang TIDAK boleh double. Double = presisi tidak stabil.
--     MODIFY COLUMN inherently idempotent, jadi tidak perlu guard.
ALTER TABLE `pengembalian`
  MODIFY COLUMN `denda` DECIMAL(12,2) NOT NULL DEFAULT 0.00;

-- 5b. Rincian denda + jejak audit + siapa yang memproses
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengembalian'
                 AND COLUMN_NAME = 'hari_terlambat') = 0,
  'ALTER TABLE `pengembalian`
     ADD COLUMN `hari_terlambat` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
     ADD COLUMN `denda_per_hari` DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
     ADD COLUMN `dasar_hitung`    VARCHAR(255)  NULL,
     ADD COLUMN `grace_hari`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
     ADD COLUMN `id_petugas`      INT NULL,
     ADD COLUMN `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- id_petugas harus signed, sama seperti petugas.id_petugas, agar bisa dipakai
-- sebagai foreign key nanti tanpa konflik tipe.
ALTER TABLE `pengembalian`
  MODIFY COLUMN `id_petugas` INT NULL;

-- 5c. Perleks kode_kembali dulu (6 -> 10) supaya ada ruang untuk backfill.
SET @sql := IF((SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengembalian'
                 AND COLUMN_NAME = 'kode_kembali') < 10,
  'ALTER TABLE `pengembalian` MODIFY COLUMN `kode_kembali` VARCHAR(10) NULL',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 5d. Backfill kode kosong. No-op pada database yang sudah bersih,
--     sehingga aman dijalankan berulang kali.
SET @row := 0;
UPDATE `pengembalian`
   SET `kode_kembali` = CONCAT('KBL', LPAD((@row := @row + 1), 8, '0'))
 WHERE `kode_kembali` IS NULL OR `kode_kembali` = '';

-- 5e. PRIMARY KEY + anti return-ganda.
--     uq_pengembalian_pinjam adalah constraint yang paling penting di file ini:
--     tanpa itu, satu peminjaman bisa di-insert berkali-kali ke pengembalian.
SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pengembalian'
                 AND INDEX_NAME = 'PRIMARY') = 0,
  'ALTER TABLE `pengembalian`
     MODIFY COLUMN `kode_kembali` VARCHAR(10) NOT NULL,
     ADD PRIMARY KEY (`kode_kembali`),
     ADD UNIQUE KEY `uq_pengembalian_pinjam` (`kode_pinjam`),
     ADD COLUMN `sudah_dibayar` TINYINT(1) NOT NULL DEFAULT 0,
     ADD COLUMN `tgl_dibayar` DATETIME NULL',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------------
-- 6. PERBAIKAN INTEGRITAS - detail_peminjaman
--    varchar(6) vs buku.kode_buku varchar(10) => truncation tersembunyi.
--    Kolom yang dipakai FK tidak bisa diubah sebelum constraint-nya dilepas.
-- ---------------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
               WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'detail_peminjaman'
                 AND CONSTRAINT_NAME = 'detail_peminjaman_ibfk_2') = 1,
  'ALTER TABLE `detail_peminjaman` DROP FOREIGN KEY `detail_peminjaman_ibfk_2`',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'detail_peminjaman'
                 AND COLUMN_NAME = 'kode_buku') < 10,
  'ALTER TABLE `detail_peminjaman` MODIFY COLUMN `kode_buku` VARCHAR(10) DEFAULT NULL',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
               WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'detail_peminjaman'
                 AND CONSTRAINT_NAME = 'detail_peminjaman_ibfk_2') = 0,
  'ALTER TABLE `detail_peminjaman`
     ADD CONSTRAINT `detail_peminjaman_ibfk_2`
     FOREIGN KEY (`kode_buku`) REFERENCES `buku` (`kode_buku`) ON DELETE CASCADE',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------------
-- 7. PERBAIKAN TRIGGER stok
--    Semula: IF NEW.stok = -1  => stok -2 TIDAK jadi ''Kosong'' (status bohong).
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `update_buku_status`;
DELIMITER //
CREATE TRIGGER `update_buku_status` BEFORE UPDATE ON `buku` FOR EACH ROW
BEGIN
  IF NEW.stok <= 0 THEN
    SET NEW.status = 'Kosong';
  ELSEIF NEW.stok = 1 THEN
    SET NEW.status = 'Dipinjam';
  ELSE
    SET NEW.status = 'Tersedia';
  END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- 8. INDEX untuk performa query katalog & pengingat
-- ---------------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'buku'
                 AND INDEX_NAME = 'idx_buku_katalog') = 0,
  'ALTER TABLE `buku`
     ADD INDEX `idx_buku_katalog` (`kategori`, `status`, `judul_buku`),
     ADD INDEX `idx_buku_pengarang` (`pengarang`)',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'peminjaman'
                 AND INDEX_NAME = 'idx_pinjam_tenggat') = 0,
  'ALTER TABLE `peminjaman`
     ADD INDEX `idx_pinjam_tenggat` (`status`, `estimasi_pinjam`),
     ADD INDEX `idx_pinjam_nim` (`nim`, `status`)',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'anggota'
                 AND INDEX_NAME = 'idx_anggota_nama') = 0,
  'ALTER TABLE `anggota`
     ADD INDEX `idx_anggota_nama` (`nama`),
     ADD INDEX `idx_anggota_aktif` (`status_mhs`, `nim`)',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
