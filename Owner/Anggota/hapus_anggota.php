<?php
/**
 * MENGHAPUS PERMANEN akun anggota.
 *
 * PERINGATAN SOAL RISIKO DATA
 * --------------------------
 * Di Database/pusaku.sql ada:
 *     CONSTRAINT `peminjaman_ibfk_1`
 *       FOREIGN KEY (`nim`) REFERENCES `anggota` ON DELETE CASCADE
 *
 * Menghapus baris `anggota` akan MENGHAPUS SECARA CASCADE seluruh riwayat
 * `peminjaman` milik anggota itu, lalu `detail_peminjaman` dan
 * `pengembalian` ikut hilang.
 *
 * Karena itu endpoint ini:
 *   1. menolak bila anggota masih punya peminjaman yang BELUM dikembalikan;
 *   2. menampilkan jumlah riwayat yang akan ikut terhapus;
 *   3. selalu mencatat aksinya di audit_log.
 *
 * Untuk menonaktifkan saja (tidak menyentuh riwayat), pakai
 * nonaktifkan_anggota.php.
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

$stmt = $conn->prepare("SELECT nim, nama, status_mhs, profil_gambar FROM anggota WHERE nim = ?");
$stmt->execute([$nim]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anggota) {
    flash('danger', 'Anggota tidak ditemukan.');
    header('Location: ' . $kembali);
    exit;
}

// 1. Jangan hapus akun yang sedang dipakai untuk login.
if (($_SESSION['nim'] ?? null) === $nim) {
    flash('danger', 'Tidak bisa menghapus akun yang sedang Anda gunakan.');
    header('Location: ' . $kembali);
    exit;
}

// 2. Tolak hapus permanen selama masih ada peminjaman yang belum kembali.
$aktif = $conn->prepare(
    "SELECT COUNT(*) FROM peminjaman
      WHERE nim = ?
        AND kode_pinjam NOT IN (SELECT kode_pinjam FROM pengembalian)"
);
$aktif->execute([$nim]);
$jmlAktif = (int) $aktif->fetchColumn();

if ($jmlAktif > 0) {
    flash('danger', 'Anggota ' . $anggota['nama'] . ' masih punya '
        . $jmlAktif . ' peminjaman yang belum dikembalikan. Selesaikan '
        . 'pengembalian terlebih dahulu, atau gunakan tombol Nonaktifkan.');
    header('Location: ' . $kembali);
    exit;
}

// 3. Hitung apa saja yang akan ikut hilang (ON DELETE CASCADE).
$totalPinjaman = (int) $conn->query(
    'SELECT COUNT(*) FROM peminjaman WHERE nim = ' . $conn->quote($nim)
)->fetchColumn();

try {
    $conn->beginTransaction();

    $hapus = $conn->prepare("DELETE FROM anggota WHERE nim = ?");
    $hapus->execute([$nim]);

    if ($hapus->rowCount() === 0) {
        $conn->rollBack();
        flash('danger', 'Gagal menghapus akun: tidak ada baris yang terpengaruh.');
        header('Location: ' . $kembali);
        exit;
    }

    // Bersihkan sisa yang tidak dikASCADE otomatis.
    $conn->prepare("DELETE FROM ingat_login WHERE peran = 'user' AND user_id = ?")->execute([$nim]);
    $conn->prepare("DELETE FROM notifikasi WHERE penerima_type = 'user' AND penerima_id = ?")->execute([$nim]);

    $conn->commit();

    // Foto profil dihapus setelah commit.
    if (!empty($anggota['profil_gambar'])) {
        \App\Services\Profil::hapusFoto($anggota['profil_gambar']);
    }

    audit('anggota.hapus_permanen', 'anggota', $nim,
          'Menghapus akun ' . $anggota['nama'] . ' (' . $totalPinjaman . ' riwayat peminjaman ikut terhapus)');

    $pesan = 'Akun ' . $anggota['nama'] . ' berhasil dihapus permanen.';
    if ($totalPinjaman > 0) {
        $pesan .= ' ' . $totalPinjaman . ' riwayat peminjaman juga ikut terhapus.';
    }
    flash('success', $pesan);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    flash('danger', 'Gagal menghapus akun: ' . $e->getMessage());
}

header('Location: ' . $kembali);
exit;
