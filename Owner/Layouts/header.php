<?php
/**
 * Layout Owner.
 *
 * Struktur identik dengan Admin; yang berbeda hanya isi menu dan peran.
 */
$peran     = 'owner';
$urlPrefix = '../../';
$namaUser  = $namaUser  ?? ($_SESSION['nama_pemilik'] ?? 'Pemilik');
$subUser   = 'Owner';
$fotoUser  = $fotoUser  ?? ($_SESSION['profil_gambar'] ?? '');

require_once __DIR__ . '/../../Config/layouts/shell_start.php';
