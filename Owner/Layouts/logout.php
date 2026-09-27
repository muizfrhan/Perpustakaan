<?php
/**
 * Logout - SUDAH DIGABUNGKAN ke file tunggal di root project.
 *
 * File ini hanya redirect supaya bookmark lama tidak menjadi 404.
 */
require_once __DIR__ . '/../../' . 'Config/bootstrap.php';

header('Location: ../../' . 'logout.php');
exit;
