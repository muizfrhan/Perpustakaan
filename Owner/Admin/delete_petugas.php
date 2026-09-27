<?php
/**
 * Menonaktifkan akun petugas (soft delete: status = 'Tidak Aktif').
 *
 * Ini endpoint DESTRUKTIF, jadi mengikuti aturan di Config/bootstrap.php:
 * wajib POST + token CSRF + require_owner() + catat di audit log.
 * Sebelumnya endpoint ini menerima GET tanpa token apa pun, sehingga
 * siapa pun bisa menonaktifkan officer hanya dengan membuka URL.
 *
 * Data riwayat peminjaman TIDAK disentuh - status akun yang diubah.
 * Untuk menghapus permanen, lihat hapus_petugas.php.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

require_owner();
require_post();
csrf_verify();

$idPetugas = (int) ($_POST['id_petugas'] ?? 0);

if ($idPetugas <= 0) {
    flash('danger', 'ID petugas tidak valid.');
    header('Location: admin.php');
    exit;
}

$stmt = $conn->prepare("SELECT id_petugas, username, nama_petugas, status FROM petugas WHERE id_petugas = ?");
$stmt->execute([$idPetugas]);
$petugas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$petugas) {
    flash('danger', 'Petugas tidak ditemukan.');
    header('Location: admin.php');
    exit;
}

// Akun owner yang dikunci di Config/pengaturan.php tidak boleh dinonaktifkan,
// karena itu satu-satunya jalan untuk masuk kembali ke panel ini.
if (owner_terkunci($petugas['username'])) {
    flash('danger', 'Akun ' . $petugas['username'] . ' terkunci dan tidak bisa dinonaktifkan.');
    header('Location: admin.php');
    exit;
}

if ($petugas['status'] !== 'Aktif') {
    flash('info', 'Akun ' . $petugas['nama_petugas'] . ' sudah tidak aktif.');
    header('Location: admin.php');
    exit;
}

try {
    $update = $conn->prepare("UPDATE petugas SET status = 'Tidak Aktif' WHERE id_petugas = ? AND status = 'Aktif'");
    $update->execute([$idPetugas]);

    if ($update->rowCount() > 0) {
        // Cabut token "ingat saya" supaya tidak bisa masuk lagi dari peramban lain.
        $conn->prepare("DELETE FROM ingat_login WHERE peran = 'admin' AND user_id = ?")->execute([$idPetugas]);

        audit('petugas.nonaktifkan', 'petugas', (string) $idPetugas,
              'Menonaktifkan akun ' . $petugas['username']);

        flash('success', 'Akun ' . $petugas['nama_petugas'] . ' berhasil dinonaktifkan.');
    } else {
        flash('info', 'Tidak ada perubahan - akun mungkin sudah tidak aktif.');
    }
} catch (PDOException $e) {
    flash('danger', 'Gagal menonaktifkan petugas: ' . $e->getMessage());
}

header('Location: admin.php');
exit;
