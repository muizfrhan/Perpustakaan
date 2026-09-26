<?php
require_once '../Config/koneksi.php'; // Sesuaikan path dengan struktur folder

if (isset($_GET['kode_buku'])) {
    $kode_buku = $_GET['kode_buku'];
    $query = $conn->prepare("SELECT * FROM buku WHERE kode_buku = :kode_buku");
    $query->bindParam(':kode_buku', $kode_buku);
    $query->execute();
    $buku = $query->fetch(PDO::FETCH_ASSOC);

    if ($buku) {
        echo json_encode($buku);
    } else {
        echo json_encode(['error' => 'Buku tidak ditemukan']);
    }
} else {
    echo json_encode(['error' => 'Kode buku tidak valid']);
}
