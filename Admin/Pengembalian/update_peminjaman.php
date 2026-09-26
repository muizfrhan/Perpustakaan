<?php
session_start();
require_once '../../Config/koneksi.php';

// Memastikan data yang diperlukan ada dalam POST
if (isset($_POST['kode_kembali'], $_POST['kode_pinjam'], $_POST['kondisi_buku'], $_POST['denda'], $_POST['status'], $_POST['pembayaran'])) {
    $kode_kembali = $_POST['kode_kembali'];
    $kode_pinjam = $_POST['kode_pinjam'];
    $kondisi_buku = $_POST['kondisi_buku'];
    $denda = $_POST['denda'];
    $status = $_POST['status'];
    $pembayaran = $_POST['pembayaran'];

    // Update data pengembalian
    $stmt = $conn->prepare("
        UPDATE pengembalian 
        SET kondisi_buku = :kondisi_buku, denda = :denda, status = :status, pembayaran = :pembayaran
        WHERE kode_kembali = :kode_kembali
    ");
    try {
        $stmt->execute([
            ':kode_kembali' => $kode_kembali,
            ':kondisi_buku' => $kondisi_buku,
            ':denda' => $denda,
            ':status' => $status,
            ':pembayaran' => $pembayaran
        ]);

        $_SESSION['message'] = "Data pengembalian berhasil diperbarui!";
        header('Location: pengembalian.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Gagal memperbarui pengembalian: " . $e->getMessage();
        header('Location: pengembalian.php');
        exit;
    }
} else {
    $_SESSION['message'] = "Data tidak lengkap.";
    header('Location: pengembalian.php');
    exit;
}
