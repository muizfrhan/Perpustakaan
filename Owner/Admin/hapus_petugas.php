<?php
/**
 * MENGHAPUS PERMANEN akun petugas.
 *
 * PERINGATAN SOAL RISIKO DATA
 * --------------------------
 * Di Database/pusaku.sql ada:
 *     CONSTRAINT `peminjaman_ibfk_2`
 *       FOREIGN KEY (`id_petugas`) REFERENCES `petugas` ON DELETE CASCADE
 *
 * Artinya menghapus baris `petugas` akan MENGHAPUS SECARA CASCADE seluruh
 * `peminjaman` milik petugas tersebut, lalu `detail_peminjaman` dan
 * `pengembalian` ikut hilang. Untuk sistem perpustakaan ini berarti
 * riwayat peminjaman & pengembalian ikut lenyap.
 *
 * Karena itu endpoint ini:
 *   1. menolak bila akun masih punya peminjaman yang BELUM dikembalikan
 *      (transaksi yang sedang berjalan tidak boleh hilang di tengah jalan);
 *   2. menampilkan jumlah riwayat yang akan ikut terhapus, supaya user
 *      bisa memutuskan dengan mata terbuka;
 *   3. selalu mencatat aksinya di audit_log.
 *
 * Bila yang diinginkan hanya menonaktifkan akun, pakai delete_petugas.php
 * (soft delete) - itu tidak menyentuh riwayat sama sekali.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

require_owner();
require_post();
csrf_verify();

$idPetugas = (int) ($_POST['id_petugas'] ?? 0);
$kembali   = 'admin.php';

if ($idPetugas <= 0) {
    flash('danger', 'ID petugas tidak valid.');
    header('Location: ' . $kembali);
    exit;
}

$stmt = $conn->prepare("SELECT id_petugas, username, nama_petugas, status, profil_gambar FROM petugas WHERE id_petugas = ?");
$stmt->execute([$idPetugas]);
$petugas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$petugas) {
    flash('danger', 'Petugas tidak ditemukan.');
    header('Location: ' . $kembali);
    exit;
}

// 1. Akun owner terkunci tidak boleh dihapus dari halaman mana pun.
if (owner_terkunci($petugas['username'])) {
    flash('danger', 'Akun ' . $petugas['username']
        . ' terkunci permanen dan tidak bisa dihapus.');
    header('Location: ' . $kembali);
    exit;
}

// 2. Tolak hapus permanen selama masih ada peminjaman yang belum kembali.
$aktif = $conn->prepare(
    "SELECT COUNT(*) FROM peminjaman
      WHERE id_petugas = ?
        AND kode_pinjam NOT IN (SELECT kode_pinjam FROM pengembalian)"
);
$aktif->execute([$idPetugas]);
$jmlAktif = (int) $aktif->fetchColumn();

if ($jmlAktif > 0) {
    flash('danger', 'Akun ' . $petugas['nama_petugas'] . ' masih menangani '
        . $jmlAktif . ' peminjaman yang belum dikembalikan. Selesaikan pengembalian '
        . 'terlebih dahulu, atau gunakan tombol Nonaktif.');
    header('Location: ' . $kembali);
    exit;
}

// 3. Hitung apa saja yang akan ikut hilang (ON DELETE CASCADE).
$totalPinjaman = (int) $conn->query(
    "SELECT COUNT(*) FROM peminjaman WHERE id_petugas = " . $idPetugas
)->fetchColumn();

try {
    $conn->beginTransaction();

    $hapus = $conn->prepare("DELETE FROM petugas WHERE id_petugas = ?");
    $hapus->execute([$idPetugas]);

    if ($hapus->rowCount() === 0) {
        $conn->rollBack();
        flash('danger', 'Gagal menghapus akun: tidak ada baris yang terpengaruh.');
        header('Location: ' . $kembali);
        exit;
    }

    // Bersihkan sisa yang tidak dikASCADE otomatis.
    $conn->prepare("DELETE FROM ingat_login WHERE peran = 'admin' AND user_id = ?")->execute([$idPetugas]);
    $conn->prepare("DELETE FROM notifikasi WHERE penerima_type = 'admin' AND penerima_id = ?")->execute([$idPetugas]);

    $conn->commit();

    // Foto profil dihapus setelah commit supaya file tidak hilang kalau
    // transaksi database-nya gagal.
    if (!empty($petugas['profil_gambar'])) {
        \App\Services\Profil::hapusFoto($petugas['profil_gambar']);
    }

    audit('petugas.hapus_permanen', 'petugas', (string) $idPetugas,
          'Menghapus akun ' . $petugas['username'] . ' (' . $totalPinjaman . ' riwayat peminjaman ikut terhapus)');

    $pesan = 'Akun ' . $petugas['nama_petugas'] . ' berhasil dihapus permanen.';
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
