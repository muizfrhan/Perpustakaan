-- ============================================================================
-- MIGRASI 003 - Foto Profil untuk Semua Peran
-- Target  : MySQL 8.0 / MariaDB 10.4 (XAMPP)
-- Prinsip : idempoten (aman dijalankan berulang kali), tidak destruktif
--
-- Konteks:
--   owner   sudah punya kolom `profil_gambar`
--   petugas sudah punya kolom `profil_gambar`
--   anggota BELUM punya kolom tersebut -> ditambah di sini supaya fitur
--   pengaturan akun bisa berlaku untuk ketiga peran tanpa terkecualikan.
--
-- Catatan desain:
--   Kolom dibuat NULL-able (default NULL), bukan NOT NULL, agar:
--   1. Baris lama yang kolomnya kosong tetap valid.
--   2. Tidak perlu memaksa UPDATE pada tabel yang bisa besar.
--   Nilai NULL dibaca sebagai "pakai inisial" oleh UI.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Tambah profil_gambar ke tabel anggota
-- ---------------------------------------------------------------------------
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'anggota'
                 AND COLUMN_NAME = 'profil_gambar') = 0,
  'ALTER TABLE `anggota`
     ADD COLUMN `profil_gambar` VARCHAR(255) NULL DEFAULT NULL
     AFTER `password`',
  'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------------
-- 1b. Perlebar kolom password menjadi VARCHAR(255)
--
-- WAJIB. Fitur ganti sandi mulai menyimpan hash (password_hash), dan hash
-- bcrypt PASSWORD_DEFAULT panjangnya 60 karakter. Kolom `anggota`.password
-- hanya VARCHAR(50) sehingga hash TERPOTONG dan password_verify() selalu
-- gagal - artinya pengguna terkunci setelah mengganti sandinya.
--
-- 255 memberi ruang untuk argon2id (97 karakter) dan algoritma masa depan.
-- MODIFY COLUMN idempoten, jadi tidak perlu guard.
-- ---------------------------------------------------------------------------
ALTER TABLE `anggota`
  MODIFY COLUMN `password` VARCHAR(255) NULL DEFAULT 'mhsudb123';

ALTER TABLE `petugas`
  MODIFY COLUMN `password` VARCHAR(255) NOT NULL;

ALTER TABLE `owner`
  MODIFY COLUMN `password` VARCHAR(255) NOT NULL;

-- ---------------------------------------------------------------------------
-- 2. Folder upload foto profil
--    Assets/uploads/profil/ dibuat oleh aplikasi (Config/Services/Profil.php)
--    saat upload pertama. Berkas .htaccess di dalam folder itu ditulis
--    oleh aplikasi, bukan dari file .sql karena isinya file, bukan SQL.
--
--    Untuk keamanan berlapis, aplikasi juga menulis .htaccess ini otomatis
--    saat folder dibuat. Kalau perlu menambahkannya manual:
--
--      <FilesMatch "\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|py|sh|htaccess)$">
--        Require all denied
--      </FilesMatch>
--
--    Catatan: 'php_flag engine off' hanya jalan di mod_php. Pada PHP-FPM
--    rules <FilesMatch> di atas sudah cukup.
-- ---------------------------------------------------------------------------
