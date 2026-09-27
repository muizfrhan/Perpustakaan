<?php
/**
 * Login Admin - SUDAH DIGABUNGKAN.
 *
 * Sekarang hanya ada SATU halaman login untuk semua peran: /login.php
 * File ini sengaja dipertahankan sebagai redirect supaya bookmark lama
 * dan tautan yang menunjuk ke sini tidak menjadi 404.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';

header('Location: ../../login.php');
exit;
