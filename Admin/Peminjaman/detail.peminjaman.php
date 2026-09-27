<?php
/**
 * Detail Peminjaman.
 *
 * QUERY, URL (?page=), dan struktur tabel TIDAK BERUBAH.
 * Yang diubah hanya markup + CSS.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'peminjaman';
include '../Layouts/header.php';

// Pagination
$limit = 10;
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$offset = ($page - 1) * $limit;

// Hitung total data
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM detail_peminjaman");
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = (int) $totalResult['total'];
$totalPages = (int) ceil($totalRows / $limit);

// Jaga agar halaman tidak melewati jumlah halaman yang ada.
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $limit;

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

$e = static fn($v) => htmlspecialchars((string) $v);

/** Nomor halaman yang ditampilkan di sekitar halaman aktif. */
$rentangHalaman = [];
if ($totalPages > 1) {
    $mulai = max(1, $page - 2);
    $akhir = min($totalPages, $mulai + 4);
    $mulai = max(1, $akhir - 4);
    for ($i = $mulai; $i <= $akhir; $i++) {
        $rentangHalaman[] = $i;
    }
}
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Detail Peminjaman</h1>
    <p class="text-muted mt-1">
      <?= number_format($totalRows, 0, ',', '.') ?> baris detail peminjaman
    </p>
  </div>

  <a href="peminjaman.php" class="btn-secondary btn-sm">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
    </svg>
    Kembali ke Peminjaman
  </a>
</section>

<!-- ================= TABEL ================= -->
<section class="card-base mt-6 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="table-base" id="detailPeminjamanTable">
      <thead>
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
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="6" class="py-14 text-center text-slate-500">
              Belum ada detail peminjaman.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td><code class="code-chip"><?= $e($row['kode_pinjam']) ?></code></td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= $e($row['judul_buku']) ?></td>
            <td>
              <?php if ($row['kondisi_buku_pinjam'] === 'Bagus'): ?>
                <span class="badge-safe">Bagus</span>
              <?php else: ?>
                <span class="badge-due">Rusak</span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap"><?= $e($row['nama_anggota']) ?></td>
            <td class="whitespace-nowrap"><?= $e($row['nama_petugas']) ?></td>
            <td class="whitespace-nowrap"><?= date('d-m-Y', strtotime($row['tgl_pinjam'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-5 py-3.5">
      <p class="text-muted">
        Halaman <span class="font-semibold text-slate-700"><?= $page ?></span> dari <?= $totalPages ?>
      </p>

      <div class="flex items-center gap-1.5">
        <a href="?page=<?= $page - 1; ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <?php if (count($rentangHalaman) > 1): ?>
          <div class="hidden items-center gap-1 sm:flex">
            <?php foreach ($rentangHalaman as $i): ?>
              <a href="?page=<?= $i; ?>"
                 class="page-link <?= $i === $page ? 'page-link-active' : '' ?>"
                 <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="?page=<?= $page + 1; ?>"
           class="btn-secondary btn-sm <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">
          Berikutnya
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </a>
      </div>
    </nav>
  <?php endif; ?>
</section>

<?php include '../Layouts/footer.php'; ?>
