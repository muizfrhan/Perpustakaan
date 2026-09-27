-- ============================================================================
-- MIGRASI 003 - Ingat Saya (login otomatis)
-- Target  : MySQL 8.0 / MariaDB 10.4 (XAMPP)
-- Prinsip : idempoten (aman dijalankan berulang kali), tidak destruktif
-- ============================================================================

-- ---------------------------------------------------------------------------
-- TABEL INGAT_LOGIN
--
-- Menyimpan token "ingat saya" sehingga pengguna tidak perlu mengetik
-- username + password di setiap kunjungan.
--
-- KENAPA TIDAK MENYIMPAN PASSWORD DI COOKIE?
--   Password di owner/petugas/anggota masih plaintext (utang teknis yang
--   sudah dicatat di Config/Services/Auth.php). Kalau pola yang sama dipakai
--   untuk cookie, siapa pun yang bisa membaca cookie langsung memegang
--   password - dan cookie itu ikut terkirim di setiap request.
--
-- POLA YANG DIPAKAI: selector : validator
--   selector  = 16 byte acak, disimpan apa adanya. Dipakai sebagai kunci
--               pencarian, jadi tidak rahasia dan boleh "bocor".
--   validator = 32 byte acak. Yang disimpan di database hanya SHA-256-nya.
--   Cookie berisi "selector:validator" (nilai mentah), lalu dibandingkan
--   dengan hash_equals() terhadap hash yang tersimpan.
--
--   Jadi nilai yang tersimpan di database tidak pernah bisa dipakai untuk
--   login ulang, dan menebak validator mustahil tanpa cookie aslinya.
--
-- ROTASI
--   Setiap kali token dipakai, validator diacak ulang. Kalau isi database
--   bocor, token yang sudah pernah dipakai tidak akan berlaku lagi.
--   Token yang dicetak saat login juga hanya bisa dipakai satu kali.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `ingat_login` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `selector`   CHAR(32)     NOT NULL COMMENT '16 byte acak, hex. Kunci pencarian token.',
  `validator`  CHAR(64)     NOT NULL COMMENT 'SHA-256 hex dari 32 byte acak.',
  `peran`      ENUM('owner','admin','user') NOT NULL,
  `user_id`    INT UNSIGNED NOT NULL COMMENT 'id_owner / id_petugas / nim',
  `ip_address` VARCHAR(45)  NULL COMMENT 'IP saat token diterbitkan (bantu audit).',
  `user_agent` VARCHAR(255) NULL,
  `expires_at` DATETIME     NOT NULL COMMENT 'Setelah ini token dibuang saat dipakai.',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ingat_selector` (`selector`),
  KEY `idx_ingat_pemakai` (`peran`, `user_id`),
  KEY `idx_ingat_kedaluwarsa` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
