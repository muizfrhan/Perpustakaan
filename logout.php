<?php
/**
 * Logout terpadu.
 *
 * Hanya ada SATU file logout untuk seluruh aplikasi. Sebelumnya tiap
 * folder punya salinan logout.php sendiri dan sidebar mengaitkan
 * "logout.php" secara relatif - sehingga halaman seperti
 * Owner/Pengembalian/pengembalian.php mengarah ke file yang tidak ada (404).
 *
 * Sidebar sekarang memakai variabel $urlLogout (lihat Config/layouts/
 * shell_start.php) sehingga selalu mengarah ke file ini.
 */
require_once __DIR__ . '/Config/bootstrap.php';
require_once __DIR__ . '/Config/koneksi.php';

use App\Services\Auth;
use App\Services\IngatLogin;

// Cabut token "ingat saya" dulu, selagi sesi masih ada isinya.
// Token dicabut berdasarkan akun yang sedang login, bukan hanya cookie,
// supaya tidak menyisakan token hidup di browser lain.
$peran = $_SESSION['peran'] ?? null;
$id    = null;

if ($peran !== null) {
    $id = match ($peran) {
        Auth::ROLE_OWNER => $_SESSION['id_owner'] ?? null,
        Auth::ROLE_ADMIN => $_SESSION['id_petugas'] ?? null,
        default          => $_SESSION['nim'] ?? null,
    };
}

IngatLogin::hapus($conn, $peran, $id !== null ? (int) $id : null);

$asal = Auth::logout();

header('Location: ' . $asal);
exit;
