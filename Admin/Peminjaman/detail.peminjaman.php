<?php
require_once '../../Config/koneksi.php';
include '../Layouts/header.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM detail_peminjaman");
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Ambil data detail_peminjaman
$result = $conn->prepare("
    SELECT dp.kode_pinjam, b.judul_buku, dp.kondisi_buku_pinjam, 
    a.nama AS nama_anggota, p.nama_petugas, pm.tgl_pinjam
    FROM detail_peminjaman dp
    INNER JOIN buku b ON dp.kode_buku = b.kode_buku
    INNER JOIN peminjaman pm ON dp.kode_pinjam = pm.kode_pinjam
    INNER JOIN anggota a ON pm.nim = a.nim
    INNER JOIN petugas p ON pm.id_petugas = p.id_petugas
    LIMIT :limit OFFSET :offset
");
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();
?>

<section class="home-section">
  <div class="mt-5">
    <h2 class="fw-bold text-dark mb-4">Detail Peminjaman</h2>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="bg-primary text-white">
          <tr>
            <th>Kode Pinjam</th>
            <th>Judul Buku</th>
            <th>Kondisi Buku</th>
            <th>Nama Anggota</th>
            <th>Nama Petugas</th>
            <th>Tanggal Pinjam</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td><?= $row['kode_pinjam']; ?></td>
            <td><?= $row['judul_buku']; ?></td>
            <td>
              <?php if ($row['kondisi_buku_pinjam'] === 'Bagus'): ?>
                <span class="badge bg-success">Bagus</span>
              <?php else: ?>
                <span class="badge bg-warning">Rusak</span>
              <?php endif; ?>
            </td>
            <td><?= $row['nama_anggota']; ?></td>
            <td><?= $row['nama_petugas']; ?></td>
            <td><?= date('d-m-Y', strtotime($row['tgl_pinjam'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <nav class="mt-3">
      <ul class="pagination">
        <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?= $page - 1; ?>">&laquo; Previous</a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?= $page + 1; ?>">Next &raquo;</a>
        </li>
      </ul>
    </nav>
  </div>
</section>
