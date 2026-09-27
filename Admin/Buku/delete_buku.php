<?php
/**
 * Mengubah status buku menjadi tidak diproduksi (stok = -1, status = 'Kosong').
 *
 * Dulu memakai dialog alert bawaan browser + window.location.href. Sekarang memakai flash()
 * dari Config/bootstrap.php supaya pesannya tampil sebagai banner di
 * buku.php, bukan dialog bawaan browser.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


$kode_buku = $_GET['kode_buku'] ?? null;

if (!$kode_buku) {
    flash('danger', 'Kode buku tidak valid.');
    header('Location: buku.php');
    exit;
}

try {
    // Cek status buku
    $stmt = $conn->prepare("SELECT status FROM buku WHERE kode_buku = :kode");
    $stmt->bindValue(':kode', $kode_buku, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        flash('danger', 'Buku tidak ditemukan.');
    } elseif ($result['status'] === 'Dipinjam') {
        // Buku masih dipinjam, status tidak boleh diubah.
        flash('warning', 'Buku masih dipinjam dan tidak dapat diubah statusnya ke Kosong.');
    } else {
        $update = $conn->prepare("UPDATE buku SET stok = -1, status = 'Kosong' WHERE kode_buku = :kode");
        $update->bindValue(':kode', $kode_buku, PDO::PARAM_STR);
        $update->execute();

        flash(
            $update->rowCount() > 0 ? 'success' : 'danger',
            $update->rowCount() > 0 ? 'Buku tidak lagi diproduksi.' : 'Gagal memperbarui buku.'
        );
    }
} catch (PDOException $e) {
    flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
}

header('Location: buku.php');
exit;
