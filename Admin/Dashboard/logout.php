<?php
session_start();

// Hapus semua sesi
session_unset();
session_destroy();

// Redirect ke halaman login
header('Location: ../Layouts/login.php');
exit;
?>
