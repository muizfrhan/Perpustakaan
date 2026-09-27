<?php
/**
 * Bootstrap aplikasi.
 *
 * Dipanggil oleh modul baru. Sengaja tidak memuat seluruh halaman lama
 * secara otomatis, supaya tidak merusak perilaku yang sudah berjalan.
 *
 * Isi:
 *  1. Error reporting yang aman (tidak membocorkan path server ke user)
 *  2. Autoloader PSR-4 sederhana untuk namespace App\
 *  3. Session hardening
 *  4. Auth guard  -> menutup celah "50 dari 57 file tanpa cek login"
 *  5. CSRF token   -> menutup celah form tanpa token
 *  6. Audit log
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set(APP_TIMEZONE);

// ---------------------------------------------------------------------------
// 1. Error reporting
// ---------------------------------------------------------------------------
$isDev = getenv('APP_DEBUG') === '1';

ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---------------------------------------------------------------------------
// 2. Autoloader (namespace App\Services\X -> Config/Services/X.php)
// ---------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = BASE_PATH . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

// ---------------------------------------------------------------------------
// 2b. Pengaturan aplikasi (owner terkunci, dll)
// ---------------------------------------------------------------------------
require_once __DIR__ . '/pengaturan.php';

// ---------------------------------------------------------------------------
// 3. Session hardening
// ---------------------------------------------------------------------------
// Session hanya relevan untuk request web. Cron/CLI tidak punya konsep cookie,
// dan session_start() di CLI akan memunculkan warning "headers already sent".
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,       // JavaScript tidak bisa baca session cookie
        'samesite' => 'Lax',      // mitigasi CSRF
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// ---------------------------------------------------------------------------
// 4. Auth guard
// ---------------------------------------------------------------------------

/**
 * Pastikan request hanya jalan untuk peran yang diizinkan.
 *
 * Dipakai sebagai baris pertama setiap halaman admin/owner/user.
 * Contoh: require_admin();
 *
 * @param string $sessionKey Kunci session yang menandai login (mis. 'id_petugas')
 * @param string $redirectKe Halaman tujuan setelah gagal
 */
function require_login(string $sessionKey, string $redirectKe = 'login.php'): void
{
    if (PHP_SAPI === 'cli') {
        throw new RuntimeException('require_login() hanya boleh dipanggil dari konteks web.');
    }

    if (empty($_SESSION[$sessionKey])) {
        header('Location: ' . $redirectKe);
        exit;
    }

    // Rotasi ID session secara berkala untuk mempersempit celah session fixation.
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    } elseif (time() - $_SESSION['created_at'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }
}

function require_admin(): void
{
    // Semua guard mengarah ke /login.php yang sama (satu form untuk semua
    // peran). Path relatif ke root project karena file ini hanya dipakai
    // oleh halaman yang berada satu level di bawah root.
    require_login('id_petugas', '../../login.php');
}

function require_owner(): void
{
    require_login('id_owner', '../../login.php');
}

function require_user(): void
{
    require_login('nim', '../login.php');
}

/**
 * Cegah aksi destruktif lewat GET.
 * Remove/delete WAJIB memakai POST + CSRF token.
 */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }
}

// ---------------------------------------------------------------------------
// 5. CSRF
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $kiriman = $_POST['csrf_token'] ?? '';

    if (!is_string($kiriman) || !hash_equals(csrf_token(), $kiriman)) {
        // Pakai 403, bukan 419. Status 419 tidak dikenal Apache 2.4 (XAMPP)
        // dan akan diteruskan sebagai 500 Internal Server Error.
        http_response_code(403);
        exit('Token CSRF tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

// ---------------------------------------------------------------------------
// 6. Output escaping
// ---------------------------------------------------------------------------

/**
 * WAJIB dipakai untuk semua data dinamis di HTML.
 * Menutup Stored XSS (delete_buku.php lama menyisipkan pesan ke dalam <script>).
 */
function e(?string $nilai): string
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------------------------------------------------------------------------
// 7. Audit log
// ---------------------------------------------------------------------------

/**
 * Catat siapa melakukan apa. Sengaja tidak melempar exception:
 * kegagalan audit tidak boleh membatalkan transaksi bisnis.
 */
function audit(string $aksi, ?string $refTable = null, ?string $refId = null, ?string $detail = null): void
{
    global $conn;

    try {
        $stmt = $conn->prepare(
            'INSERT INTO audit_log (actor_type, actor_id, aksi, ref_table, ref_id, detail, ip_address, user_agent)
             VALUES (:actor_type, :actor_id, :aksi, :ref_table, :ref_id, :detail, :ip, :ua)'
        );

        $stmt->execute([
            ':actor_type' => current_actor_type(),
            ':actor_id'   => current_actor_id(),
            ':aksi'       => $aksi,
            ':ref_table'  => $refTable,
            ':ref_id'     => $refId,
            ':detail'     => $detail,
            ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua'         => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $ex) {
        error_log('Audit log gagal: ' . $ex->getMessage());
    }
}

function current_actor_type(): string
{
    return match (true) {
        isset($_SESSION['id_owner'])   => 'Owner',
        isset($_SESSION['id_petugas']) => 'Petugas',
        isset($_SESSION['nim'])        => 'Anggota',
        default                        => 'Anonim',
    };
}

function current_actor_id(): ?int
{
    return match (true) {
        isset($_SESSION['id_owner'])   => (int) $_SESSION['id_owner'],
        isset($_SESSION['id_petugas']) => (int) $_SESSION['id_petugas'],
        isset($_SESSION['nim'])        => (int) $_SESSION['nim'],
        default                        => null,
    };
}

// ---------------------------------------------------------------------------
// 8. Redirect dengan pesan flash
// ---------------------------------------------------------------------------

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambil_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}
