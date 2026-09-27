<?php
/**
 * Detail Pengembalian (fragment AJAX).
 *
 * Dimuat ke dalam container modal. Tidak ada <html>/<body> - hanya
 * markup self-contained agar tampil benar di dalam .modal-body.
 */
require_once '../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


$e = static fn($v) => htmlspecialchars((string) $v);

/** Peta status kembali -> kelas badge. */
$badgeStatusKembali = [
    'Aman'      => 'badge-safe',
    'Terlambat' => 'badge-due',
    'Hilang'    => 'badge-late',
    'Rusak'     => 'badge-neutral',
];

if (isset($_GET['id'])) {
    $idKembali = (int)$_GET['id'];

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

    if ($detail):
        $badge = $badgeStatusKembali[$detail['status_kembali']] ?? 'badge-neutral';
        ?>
        <dl>
          <div class="dl-row">
            <dt class="dl-term">ID</dt>
            <dd class="dl-desc text-right"><?= $e($detail['id_kembali']) ?></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Nama Anggota</dt>
            <dd class="dl-desc text-right"><?= $e($detail['nama_anggota']) ?></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">NIM</dt>
            <dd class="dl-desc text-right"><code class="code-chip"><?= $e($detail['nim']) ?></code></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Kode Buku</dt>
            <dd class="dl-desc text-right"><code class="code-chip"><?= $e($detail['kode_buku']) ?></code></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Judul Buku</dt>
            <dd class="dl-desc text-right"><?= $e($detail['judul_buku']) ?></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Nama Petugas</dt>
            <dd class="dl-desc text-right"><?= $e($detail['nama_petugas']) ?></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Tanggal Pinjam</dt>
            <dd class="dl-desc text-right"><?= $e($detail['tgl_pinjam']) ?></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Tanggal Kembali</dt>
            <dd class="dl-desc text-right"><?= $e($detail['tgl_kembali']) ?></dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Status Kembali</dt>
            <dd class="dl-desc text-right">
              <span class="<?= $badge ?>"><?= $e($detail['status_kembali']) ?></span>
            </dd>
          </div>
          <div class="dl-row">
            <dt class="dl-term">Denda</dt>
            <dd class="dl-desc text-right">Rp<?= number_format((float) $detail['denda'], 2, ',', '.') ?></dd>
          </div>
        </dl>
    <?php else: ?>
        <div class="alert-danger" role="alert">
          <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
          <p>Data tidak ditemukan.</p>
        </div>
    <?php endif;
} else {
    echo '<div class="alert-danger" role="alert">'
       . '<svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">'
       . '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-6.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />'
       . '</svg>'
       . '<p>ID tidak valid.</p></div>';
}
