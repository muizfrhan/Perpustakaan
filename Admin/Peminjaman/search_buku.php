<?php
require_once '../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


$query = $_GET['query'] ?? '';

$stmt = $conn->prepare("
    SELECT kode_buku, judul_buku 
    FROM buku 
    WHERE (kode_buku LIKE :query OR judul_buku LIKE :query) AND stok > 0
");
$stmt->execute([':query' => "%$query%"]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
