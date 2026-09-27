<?php
require_once '../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


$query = $_GET['query'] ?? '';

$stmt = $conn->prepare("
    SELECT nim, nama 
    FROM anggota 
    WHERE (nim LIKE :query OR nama LIKE :query) AND status_mhs = 'Aktif'
");
$stmt->execute([':query' => "%$query%"]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
