<?php

namespace App\Services;

use PDO;
use Throwable;

/**
 * "Ingat Saya" - login otomatis tanpa mengetik ulang.
 *
 * CARA KERJA
 *   Saat login berhasil DAN centang "Ingat saya" diklik:
 *     1. Token baru diterbitkan (selector + validator).
 *     2. Cookie `pusaku_ingat` = "selector:validator" dikirim ke browser.
 *
 *   Saat membuka login.php tanpa sesi:
 *     1. Cookie dicek, selector dicari di database.
 *     2. SHA-256(validator) dibandingkan dengan hash yang tersimpan.
 *     3. Cocok -> sesi dibuat, validator diacak ulang (rotasi), redirect.
 *
 * KEAMANAN
 *   - Yang disimpan di database hanya hash validator, bukan nilai aslinya.
 *   - Perbandingan memakai hash_equals() (waktu konstan).
 *   - Cookie HttpOnly + SameSite=Lax + Secure otomatis saat memakai HTTPS.
 *   - Setiap pemakaian memutar ulang token, jadi token yang bocor cepat mati.
 *   - Maksimal satu token aktif per akun. Login baru mencabut token lama.
 *   - Akun yang sudah dinonaktifkan atau dihapus tidak bisa di-login-kan
 *     otomatis, karena datanya selalu dibaca ulang dari tabel aslinya.
 *
 * CATATAN
 *   Token remember-me melengkapi session biasa, bukan menggantikannya.
 *   Sesi tetap memakai cookie session PHP seperti sekarang. Bedanya hanya
 *   masaRemember-me yang 30 hari dan bisa diperpanjang di tengah jalan.
 */
final class IngatLogin
{
    /** Nama cookie. Prefix aplikasi supaya tidak bentrok dengan yang lain. */
    public const COOKIE = 'pusaku_ingat';

    /** Lama berlaku token, dalam hari. */
    public const MASA_HARI = 30;

    /** Jarak minimum dua kali membersihkan token lapsuk (detik). */
    private const JEDA_BERSIH = 3600;

    /**
     * Kapan terakhir kali token kedaluwarsa dibersihkan.
     *
     * Pembersihan dilakukan saat ada kesempatan, bukan dari cron, supaya
     * tidak bergantung pada Task Scheduler yang belum tentu dijalankan.
     */
    private static int $terakhirBersih = 0;

    // ---------------------------------------------------------------------
    // Penerbitan & pembatalan
    // ---------------------------------------------------------------------

    /** Apakah request ini membawa cookie "ingat"? */
    public static function ada(): bool
    {
        return isset($_COOKIE[self::COOKIE]) && is_string($_COOKIE[self::COOKIE]);
    }

    /**
     * Terbitkan token baru untuk akun yang baru saja login.
     *
     * @param array $hasil Bentuk kembalian Auth::attempt():
     *                     ['role' => string, 'id' => mixed, ...]
     */
    public static function daftarkan(PDO $conn, array $hasil): void
    {
        $peran = (string) $hasil['role'];
        $id    = (int) $hasil['id'];

        // Satu akun hanya boleh punya satu token aktif. Token lama dicabut
        // supaya cookie di browser lain ikut tidak berlaku.
        self::cabutSemua($conn, $peran, $id);

        $selector  = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));

        // Masa berlaku dihitung di PHP, bukan lewat DATE_ADD/INTERVAL,
        // supaya tidak bergantung pada perilaku placeholder PDO.
        $kedaluwarsa = date('Y-m-d H:i:s', time() + self::MASA_HARI * 86400);

        $stmt = $conn->prepare(
            'INSERT INTO ingat_login
                (selector, validator, peran, user_id, ip_address, user_agent, expires_at)
             VALUES
                (:selector, :validator, :peran, :user_id, :ip, :ua, :kedaluwarsa)'
        );

        $stmt->execute([
            ':selector'    => $selector,
            ':validator'   => hash('sha256', $validator),
            ':peran'       => $peran,
            ':user_id'     => $id,
            ':ip'          => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ':ua'          => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':kedaluwarsa' => $kedaluwarsa,
        ]);

        self::kirimCookie($selector . ':' . $validator);
    }

    /**
     * Cabut token milik akun yang sedang logout, lalu bersihkan cookie.
     */
    public static function hapus(PDO $conn, ?string $peran = null, ?int $id = null): void
    {
        // Cabut token yang sedang dipegang browser ini.
        if (self::ada()) {
            $selector = self::ambilSelector();

            if ($selector !== null) {
                self::hapusSelector($conn, $selector);
            }
        }

        // Cabut juga token lain milik akun ini, supaya tidak menyisakan
        // token yang masih hidup di browser berbeda.
        if ($peran !== null && $id !== null) {
            self::cabutSemua($conn, $peran, $id);
        }

        self::lupakanCookie();
    }

    /** Buang semua token milik satu akun. */
    public static function cabutSemua(PDO $conn, string $peran, int $id): void
    {
        try {
            $stmt = $conn->prepare('DELETE FROM ingat_login WHERE peran = :p AND user_id = :u');
            $stmt->execute([':p' => $peran, ':u' => $id]);
        } catch (Throwable $ex) {
            error_log('IngatLogin: gagal cabut token - ' . $ex->getMessage());
        }
    }

    // ---------------------------------------------------------------------
    // Pemakaian
    // ---------------------------------------------------------------------

    /**
     * Coba lanjutkan sesi dari cookie "ingat".
     *
     * @return array|null Bentuk sama dengan Auth::attempt() (tanpa 'password'),
     *                     atau null bila tidak ada token yang bisa dipakai.
     */
    public static function cobaLanjutkan(PDO $conn): ?array
    {
        if (!self::ada()) {
            return null;
        }

        self::bersihkanLapsed($conn);

        $selector  = self::ambilSelector();
        $validator = self::ambilValidator();

        // Bentuk cookie rusak: jangan sentuh database sama sekali.
        if ($selector === null || $validator === null) {
            self::lupakanCookie();
            return null;
        }

        $stmt = $conn->prepare(
            'SELECT validator, peran, user_id, expires_at
               FROM ingat_login
              WHERE selector = :s
              LIMIT 1'
        );
        $stmt->execute([':s' => $selector]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        // Selector tidak dikenal: cookie sudah basi atau sudah dicabut.
        if (!$token) {
            self::lupakanCookie();
            return null;
        }

        // Sudah lewat masa berlaku: baris dibuang, cookie dibersihkan.
        if (strtotime((string) $token['expires_at']) <= time()) {
            self::hapusSelector($conn, $selector);
            self::lupakanCookie();
            return null;
        }

        // Validator tidak cocok. Dua kemungkinan: cookie diedit, atau ada
        // yang memalsukan selector milik orang lain. Cabut token aslinya
        // supaya tidak bisa dicoba berulang kali.
        if (!hash_equals((string) $token['validator'], hash('sha256', $validator))) {
            self::hapusSelector($conn, $selector);
            self::lupakanCookie();
            return null;
        }

        $peran = (string) $token['peran'];
        $id    = (int) $token['user_id'];

        // Akun bisa saja sudah dihapus, dinonaktifkan, atau berubah sejak
        // token diterbitkan. Selalu baca ulang dari tabel aslinya.
        $akun = Auth::loadByRole($conn, $peran, $id);

        if ($akun === null) {
            self::cabutSemua($conn, $peran, $id);
            self::lupakanCookie();
            return null;
        }

        // Rotasi: validator yang barusan dipakai langsung tidak berlaku lagi.
        self::daftarkan($conn, $akun);

        return $akun;
    }

    // ---------------------------------------------------------------------
    // Internal
    // ---------------------------------------------------------------------

    /** Bagian selector dari cookie, atau null kalau tidak berbentuk benar. */
    private static function ambilSelector(): ?string
    {
        $selector = self::pecah(0);

        return preg_match('/^[0-9a-f]{32}$/', $selector ?? '') ? $selector : null;
    }

    /** Bagian validator dari cookie, atau null kalau tidak berbentuk benar. */
    private static function ambilValidator(): ?string
    {
        $validator = self::pecah(1);

        return preg_match('/^[0-9a-f]{64}$/', $validator ?? '') ? $validator : null;
    }

    /** Ambil bagian ke-$indeks dari cookie "selector:validator". */
    private static function pecah(int $indeks): ?string
    {
        $cookie = (string) ($_COOKIE[self::COOKIE] ?? '');
        $bagian = explode(':', $cookie);

        return $bagian[$indeks] ?? null;
    }

    private static function hapusSelector(PDO $conn, string $selector): void
    {
        try {
            $stmt = $conn->prepare('DELETE FROM ingat_login WHERE selector = :s');
            $stmt->execute([':s' => $selector]);
        } catch (Throwable $ex) {
            error_log('IngatLogin: gagal hapus token - ' . $ex->getMessage());
        }
    }

    /**
     * Buang token yang sudah lewat masa berlaku.
     *
     * Dijalankan paling sering sekali per jam per request supaya tidak
     * membebani query pada halaman yang paling ramai.
     */
    private static function bersihkanLapsed(PDO $conn): void
    {
        if (time() - self::$terakhirBersih < self::JEDA_BERSIH) {
            return;
        }

        self::$terakhirBersih = time();

        try {
            $conn->exec('DELETE FROM ingat_login WHERE expires_at <= NOW()');
        } catch (Throwable $ex) {
            error_log('IngatLogin: gagal bersihkan token lapsuk - ' . $ex->getMessage());
        }
    }

    /** Tulis cookie remember-me. Aman dipanggil sebelum header terkirim. */
    private static function kirimCookie(string $nilai): void
    {
        if (headers_sent()) {
            error_log('IngatLogin: header sudah terkirim, cookie tidak bisa dibuat.');
            return;
        }

        setcookie(self::COOKIE, $nilai, [
            'expires'  => time() + self::MASA_HARI * 86400,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly'  => true,   // JavaScript tidak bisa membaca token
            'samesite' => 'Lax',   // mitigasi CSRF
        ]);
    }

    /** Minta browser membuang cookie remember-me. */
    public static function lupakanCookie(): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE, '', [
            'expires'  => time() - 42000,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[self::COOKIE]);
    }
}
