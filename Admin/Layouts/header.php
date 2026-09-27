<?php
/**
 * Layout Admin.
 *
 * Shell bersama (Config/layouts/shell_start.php) sudah memuat <head>,
 * sidebar, dan membuka <main>. Halaman cukup:
 *
 *   <?php $menuAktif = 'buku'; include '../Layouts/header.php'; ?>
 *   ...konten...
 *   <?php include '../Layouts/footer.php'; ?>
 *
 * Nama file, variabel, dan URL tidak berubah dari versi lama.
 */

$peran     = 'admin';
$urlPrefix = '../../';
$namaUser  = $namaUser  ?? ($_SESSION['nama_petugas'] ?? 'Petugas');
$subUser   = 'Admin';
$fotoUser  = $fotoUser  ?? ($_SESSION['profil_gambar'] ?? '');

require_once __DIR__ . '/../../Config/layouts/shell_start.php';
