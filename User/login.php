<?php
/**
 * Login Anggota - SUDAH DIGABUNGKAN.
 *
 * Sekarang hanya ada SATU halaman login untuk semua peran: /login.php,
 * di mana NIM cukup diketik pada kolom "Username atau NIM".
 * File ini dipertahankan sebagai redirect supaya bookmark lama tidak 404.
 */
require_once __DIR__ . '/../Config/bootstrap.php';

header('Location: ../login.php');
exit;
