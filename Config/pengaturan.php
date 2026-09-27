<?php
/**
 * Pengaturan aplikasi yang disentralkan.
 *
 * Dipanggil dari Config/bootstrap.php. Semua halaman cukup require
 * bootstrap.php untuk mendapat konstanta dan helper di bawah.
 */

/**
 * Username owner yang akunnya dikunci permanen.
 *
 * Akun dengan username di daftar ini:
 *   - tidak bisa dinonaktifkan dari halaman Kelola Admin
 *   - tidak bisa dihapus permanen dari halaman mana pun
 *
 * Tujuannya: Guarantee ada minimal satu akun owner yang tetap bisa masuk,
 * supaya tidak ada kondisi "semua owner terhapus" yang membuat aplikasi
 * tidak bisa dikelola lagi.
 *
 * Format: username persis seperti tersimpan di tabel `owner` (case-insensitive).
 */
const OWNER_TERKUNCI = [
    'hansz',
];

/**
 * Apakah username owner ini terkunci permanen?
 */
function owner_terkunci(?string $username): bool
{
    $username = mb_strtolower(trim((string) $username));

    if ($username === '') {
        return false;
    }

    return in_array($username, array_map('mb_strtolower', OWNER_TERKUNCI), true);
}
