<?php
/**
 * Entry point aplikasi.
 *
 * Langsung ke login terpadu di /login.php. Tidak ada lagi pemilihan peran:
 * satu form untuk owner, admin (petugas), dan anggota.
 */
header('Location: login.php');
exit;
