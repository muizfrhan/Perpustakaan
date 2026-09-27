<?php
/**
 * Menonaktifkan akun anggota (soft delete: status_mhs = 'Tidak Aktif').
 *
 * Endpoint DESTRUKTIF: wajib POST + CSRF + require_owner() + audit log.
 * Data riwayat peminjaman anggota TIDAK disentuh. Untuk hapus permanen,
 * lihat hapus_anggota.php.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

require_owner();
require_post();
csrf_verify();

$nim     = trim((string) ($_POST['nim'] ?? ''));
$kembali = 'anggota.php';

if ($nim === '') {
    flash('danger', 'NIM tidak valid.');
    header('Location: ' . $kembali);
    exit;
}

$stmt = $conn->prepare("SELECT nim, nama, status_mhs FROM anggota WHERE nim = ?");
$stmt->execute([$nim]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anggota) {
    flash('danger', 'Anggota tidak ditemukan.');
    header('Location: ' . $kembali);
    exit;
}

if ($anggota['status_mhs'] !== 'Aktif') {
    flash('info', 'Akun ' . $anggota['nama'] . ' sudah tidak aktif.');
    header('Location: ' . $kembali);
    exit;
}

try {
    $update = $conn->prepare("UPDATE anggota SET status_mhs = 'Tidak Aktif' WHERE nim = ? AND status_mhs = 'Aktif'");
    $update->execute([$nim]);

    if ($update->rowCount() > 0) {
        // Cabut token "ingat saya" agar tidak bisa masuk lagi.
        $conn->prepare("DELETE FROM ingat_login WHERE peran = 'user' AND user_id = ?")->execute([$nim]);

        audit('anggota.nonaktifkan', 'anggota', $nim, 'Menonaktifkan akun ' . $anggota['nama']);

        flash('success', 'Akun ' . $anggota['nama'] . ' berhasil dinonaktifkan.');
    } else {
        flash('info', 'Tidak ada perubahan - akun mungkin sudah tidak aktif.');
    }
} catch (PDOException $e) {
    flash('danger', 'Gagal menonaktifkan anggota: ' . $e->getMessage());
}

header('Location: ' . $kembali);
exit;
