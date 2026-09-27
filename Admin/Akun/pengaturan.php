<?php
/**
 * Pengaturan akun - Petugas (Admin).
 *
 * Halaman ini tipis; seluruh logika & UI ada di Config/partials/profil.php
 * agar Admin, Owner, dan Anggota memakai sumber yang sama.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

require_admin();

$peranProfil      = 'petugas';
$urlPrefix = '../../';

$menuAktif = 'pengaturan';
include __DIR__ . '/../Layouts/header.php';

require __DIR__ . '/../../Config/partials/profil.php';

include __DIR__ . '/../Layouts/footer.php';
