<?php
/**
 * Riwayat Peminjaman Anggota
 *
 * QUERY DAN VARIABELNYA TIDAK BERUBAH dari versi lama.
 * Yang diubah hanya markup + CSS (Bootstrap -> Tailwind).
 */
require_once '../Config/koneksi.php';
include 'header.php';

require_once '../Config/bootstrap.php';

use App\Services\DendaService;

$denda = new DendaService($conn);

$nim = $_SESSION['nim'];

// Query History
$queryHistory = $conn->prepare("
    SELECT 
        p.kode_pinjam,
        p.tgl_pinjam,
        p.estimasi_pinjam,
        p.status AS status_peminjaman,
        pg.tgl_kembali,
        pg.kondisi_buku,
        pg.denda,
        pg.status AS status_pengembalian,
        pg.pembayaran,
        b.judul_buku,
        b.cover
    FROM 
        peminjaman p
    LEFT JOIN 
        pengembalian pg ON p.kode_pinjam = pg.kode_pinjam
    LEFT JOIN 
        buku b ON p.kode_buku = b.kode_buku
    WHERE 
        p.nim = :nim
    ORDER BY 
        p.tgl_pinjam DESC
");
$queryHistory->bindParam(':nim', $nim);
$queryHistory->execute();
$history = $queryHistory->fetchAll(PDO::FETCH_ASSOC);

// Query Statistik
$queryTotal = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE nim = :nim");
$queryTotal->bindParam(':nim', $nim);
$queryTotal->execute();
$totalPinjaman = $queryTotal->fetch(PDO::FETCH_ASSOC)['total'];

$queryLate = $conn->prepare("
    SELECT 
        SUM(
            CASE WHEN pg.tgl_kembali > p.estimasi_pinjam 
            THEN DATEDIFF(pg.tgl_kembali, p.estimasi_pinjam) 
            ELSE 0 
            END
        ) as total_hari 
    FROM peminjaman p 
    LEFT JOIN pengembalian pg ON p.kode_pinjam = pg.kode_pinjam 
    WHERE p.nim = :nim
");
$queryLate->bindParam(':nim', $nim);
$queryLate->execute();
$totalTerlambat = $queryLate->fetch(PDO::FETCH_ASSOC)['total_hari'] ?? 0;

// Hitung persentase progress bar
$progressPinjaman = min($totalPinjaman * 6.25, 100); // 16 buku = 100%
$progressTerlambat = min($totalTerlambat * 7.5, 100); // 13 hari = 100%

// Ringkasan tambahan (data turunan, bukan query baru)
$jumlahLunas    = count(array_filter($history, fn($h) => (float) ($h['denda'] ?? 0) <= 0));
$jumlahBelumLunas = count($history) - $jumlahLunas;
$totalDenda = array_sum(array_map(fn($h) => (float) ($h['denda'] ?? 0), $history));
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Riwayat Peminjaman</h1>
    <p class="text-muted mt-1">Seluruh aktivitas peminjaman dan pengembalian buku Anda</p>
  </div>

  <a href="home.php" class="btn-primary btn-sm shrink-0">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
    Cari Buku
  </a>
</section>

<!-- ================= STAT CARDS ================= -->
<section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

  <div class="stat-card flex-col items-start gap-2 lg:flex-row lg:items-center">
    <span class="stat-icon bg-brand-50 text-brand-600">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
    </span>
    <div class="w-full min-w-0">
      <p class="text-2xl font-bold leading-none text-slate-900"><?= $totalPinjaman ?></p>
      <p class="text-muted mt-1.5">Total Peminjaman</p>
      <div class="progress-track mt-2">
        <div class="progress-fill bg-brand-500" style="width: <?= max(4, $progressPinjaman) ?>%"></div>
      </div>
    </div>
  </div>

  <div class="stat-card flex-col items-start gap-2 lg:flex-row lg:items-center">
    <span class="stat-icon bg-rose-50 text-rose-600">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
      </svg>
    </span>
    <div class="w-full min-w-0">
      <p class="text-2xl font-bold leading-none text-rose-600"><?= $totalTerlambat ?></p>
      <p class="text-muted mt-1.5">Total Hari Terlambat</p>
      <div class="progress-track mt-2">
        <div class="progress-fill bg-rose-500" style="width: <?= max(4, $progressTerlambat) ?>%"></div>
      </div>
    </div>
  </div>

  <div class="stat-card flex-col items-start gap-2 lg:flex-row lg:items-center">
    <span class="stat-icon bg-emerald-50 text-emerald-600">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </span>
    <div class="w-full min-w-0">
      <p class="text-2xl font-bold leading-none text-slate-900"><?= $jumlahLunas ?></p>
      <p class="text-muted mt-1.5">Transaksi Lunas</p>
    </div>
  </div>

  <div class="stat-card flex-col items-start gap-2 lg:flex-row lg:items-center">
    <span class="stat-icon bg-amber-50 text-amber-600">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </span>
    <div class="w-full min-w-0">
      <p class="truncate text-2xl font-bold leading-none <?= $totalDenda > 0 ? 'text-amber-600' : 'text-slate-900' ?>">
        <?= $totalDenda > 0 ? 'Rp' . number_format($totalDenda, 0, ',', '.') : 'Rp0' ?>
      </p>
      <p class="text-muted mt-1.5">Akumulasi Denda</p>
    </div>
  </div>

</section>

<!-- ================= TABEL RIWAYAT ================= -->
<section class="mt-8">
  <h2 class="text-section mb-4">Daftar Transaksi</h2>

  <?php if (empty($history)): ?>
    <div class="card-base flex flex-col items-center justify-center px-6 py-14 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </span>
      <h3 class="mt-4 text-base font-semibold text-slate-900">Belum ada riwayat peminjaman</h3>
      <p class="text-muted mt-1 max-w-sm">Riwayat akan muncul setelah Anda meminjam buku pertama kali.</p>
      <a href="home.php" class="btn-primary btn-sm mt-5">Jelajahi Katalog</a>
    </div>

  <?php else: ?>
    <div class="card-base overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th class="w-16"></th>
              <th>Judul Buku</th>
              <th class="whitespace-nowrap">Dipinjam</th>
              <th class="whitespace-nowrap">Batas Kembali</th>
              <th class="whitespace-nowrap">Dikembalikan</th>
              <th class="whitespace-nowrap text-right">Denda</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $h):
              $dendaNilai = (float) ($h['denda'] ?? 0);
              $sisaHari   = $denda->sisaHari($h['estimasi_pinjam']);
              $masihDipinjam = empty($h['tgl_kembali']);

              if (!$masihDipinjam && $dendaNilai > 0) {
                $kelasBadge = 'badge-late';
                $teksBadge = 'Belum Lunas';
              } elseif (!$masihDipinjam) {
                $kelasBadge = 'badge-safe';
                $teksBadge = 'Lunas';
              } elseif ($sisaHari < 0) {
                $kelasBadge = 'badge-late';
                $teksBadge = 'Terlambat ' . abs($sisaHari) . ' hari';
              } elseif ($sisaHari <= 3) {
                $kelasBadge = 'badge-due';
                $teksBadge = $sisaHari === 0 ? 'Jatuh tempo hari ini' : $sisaHari . ' hari lagi';
              } else {
                $kelasBadge = 'badge-info';
                $teksBadge = $sisaHari . ' hari lagi';
              }
            ?>
              <tr>
                <td>
                  <img src="../Assets/uploads/<?= htmlspecialchars($h['cover'] ?? 'default-cover.jpg') ?>"
                       alt="" loading="lazy"
                       class="h-14 w-10 rounded-lg object-cover" />
                </td>
                <td>
                  <p class="max-w-[16rem] truncate text-sm font-semibold text-slate-900">
                    <?= htmlspecialchars($h['judul_buku'] ?? '-') ?>
                  </p>
                  <code class="mt-0.5 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-500">
                    <?= htmlspecialchars($h['kode_pinjam']) ?>
                  </code>
                </td>
                <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($h['tgl_pinjam'])) ?></td>
                <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($h['estimasi_pinjam'])) ?></td>
                <td class="whitespace-nowrap"><?= $h['tgl_kembali'] ? date('d/m/Y', strtotime($h['tgl_kembali'])) : '-' ?></td>
                <td class="whitespace-nowrap text-right font-semibold <?= $dendaNilai > 0 ? 'text-rose-600' : 'text-slate-400' ?>">
                  <?= $dendaNilai > 0 ? 'Rp' . number_format($dendaNilai, 0, ',', '.') : '-' ?>
                </td>
                <td><span class="<?= $kelasBadge ?>"><?= $teksBadge ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
