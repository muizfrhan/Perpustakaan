<?php
require_once '../../Config/koneksi.php';

$query = $_GET['query'] ?? '';

$stmt = $conn->prepare("
    SELECT kode_buku, judul_buku 
    FROM buku 
    WHERE (kode_buku LIKE :query OR judul_buku LIKE :query) AND stok > 0
");
$stmt->execute([':query' => "%$query%"]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
