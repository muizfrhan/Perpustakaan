<?php
// Konfigurasi koneksi ke database
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'pusaku';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>

