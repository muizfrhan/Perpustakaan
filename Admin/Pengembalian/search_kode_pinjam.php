<?php
require_once '../../Config/koneksi.php';

$kodePinjam = $_GET['kode_pinjam'] ?? '';

$stmt = $conn->prepare("
    SELECT peminjaman.kode_pinjam, anggota.nama AS nama_anggota
    FROM peminjaman
    JOIN anggota ON peminjaman.nim = anggota.nim
    WHERE peminjaman.kode_pinjam LIKE :kodePinjam
    AND peminjaman.kode_pinjam NOT IN (SELECT kode_pinjam FROM pengembalian)
");
$stmt->execute([':kodePinjam' => "%$kodePinjam%"]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
?>