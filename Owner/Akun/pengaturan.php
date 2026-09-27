<?php
/**
 * Pengaturan akun - Owner.
 *
 * Berbeda dari Owner/Admin/admin.php yang dipakai untuk mengelola akun
 * petugas LAIN. Halaman ini hanya untuk profil milik Owner yang login.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

require_owner();

$peranProfil      = 'owner';
$urlPrefix = '../../';

$menuAktif = 'pengaturan';
include __DIR__ . '/../Layouts/header.php';

require __DIR__ . '/../../Config/partials/profil.php';

include __DIR__ . '/../Layouts/footer.php';
