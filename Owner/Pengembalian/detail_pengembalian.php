<?php
/**
 * Detail Pengembalian (fragment AJAX - Panel Owner).
 *
 * Dimuat ke dalam .modal-body. Tidak ada <html>/<body> -
 * Traveler hanya mengambil isi respons.
 * QUERY dan parameter ?id= TIDAK BERUBAH.
 */
require_once '../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_owner();


if (!isset($_GET['id'])) {
    echo '<div class="alert-danger" role="alert">ID tidak valid.</div>';
    return;
}

$idKembali = (int) $_GET['id'];

$query = $conn->prepare("
    SELECT
        pengembalian.id_kembali,
        anggota.nim,
        anggota.nama AS nama_anggota,
        buku.kode_buku,
        buku.judul_buku,
        petugas.nama_petugas,
        peminjaman.tgl_pinjam,
        pengembalian.tgl_kembali,
        pengembalian.status_kembali,
        pengembalian.denda
    FROM pengembalian
    JOIN peminjaman ON pengembalian.id_pinjam = peminjaman.id_pinjam
    JOIN anggota ON peminjaman.nim = anggota.nim
    JOIN buku ON peminjaman.kode_buku = buku.kode_buku
    JOIN petugas ON peminjaman.id_petugas = petugas.id_petugas
    WHERE pengembalian.id_kembali = :id
");
$query->bindValue(':id', $idKembali, PDO::PARAM_INT);
$query->execute();
$detail = $query->fetch(PDO::FETCH_ASSOC);

if (!$detail) {
    echo '<div class="alert-danger" role="alert">Data tidak ditemukan.</div>';
    return;
}

/** Peta status kembali -> kelas badge. */
$badgeKembali = [
    'Aman'     => 'badge-available',
    'Terlambat' => 'badge-late',
    'Hilang'   => 'badge-empty',
    'Rusak'    => 'badge-due',
];
$badge = $badgeKembali[$detail['status_kembali']] ?? 'badge-neutral';

$info = [
    'ID'              => (string) $detail['id_kembali'],
    'Nama Anggota'    => (string) $detail['nama_anggota'],
    'NIM'             => (string) $detail['nim'],
    'Kode Buku'       => (string) $detail['kode_buku'],
    'Judul Buku'      => (string) $detail['judul_buku'],
    'Nama Petugas'    => (string) $detail['nama_petugas'],
    'Tanggal Pinjam'  => (string) $detail['tgl_pinjam'],
    'Tanggal Kembali' => (string) $detail['tgl_kembali'],
];
?>

<dl>
  <?php foreach ($info as $label => $value): ?>
    <div class="dl-row">
      <dt class="dl-term"><?= htmlspecialchars($label) ?></dt>
      <dd class="dl-desc text-right"><?= htmlspecialchars($value !== '' ? $value : '-') ?></dd>
    </div>
  <?php endforeach; ?>

  <div class="dl-row">
    <dt class="dl-term">Status Kembali</dt>
    <dd class="dl-desc text-right">
      <span class="<?= $badge ?>"><?= htmlspecialchars((string) $detail['status_kembali']) ?></span>
    </dd>
  </div>

  <div class="dl-row">
    <dt class="dl-term">Denda</dt>
    <dd class="dl-desc text-right">Rp<?= number_format((float) $detail['denda'], 2, ',', '.') ?></dd>
  </div>
</dl>
