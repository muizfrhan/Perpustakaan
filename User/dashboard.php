<?php
/**
 * Dashboard Anggota
 *
 * Halaman BARU - tidak mengubah file yang sudah ada.
 * Menampilkan statistik personal, buku yang sedang dipinjam, dan
 * riwayat pengembalian beserta status denda.
 *
 * Fine dihitung dengan DendaService yang SAMA dengan dipakai modul
 * pengembalian di sisi admin, sehingga angka yang dilihat anggota
 * tidak mungkin berbeda dengan yang ditagih petugas.
 */
require_once '../Config/koneksi.php';
require_once '../Config/bootstrap.php';

include 'header.php';

use App\Services\DendaService;
use App\Services\KondisiBuku;
use App\Services\ReminderService;

$nim    = (int) $_SESSION['nim'];
$denda  = new DendaService($conn);
$reminder = new ReminderService($conn, $denda);

// --- Profil singkat ---------------------------------------------------------
$stmt = $conn->prepare('SELECT nama, nim, jurusan, kelas, status_mhs FROM anggota WHERE nim = :nim');
$stmt->execute([':nim' => $nim]);
$mhs = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nama' => '-', 'nim' => $nim, 'jurusan' => '-', 'kelas' => '-', 'status_mhs' => 'Aktif'];

// --- Buku yang SEDANG dipinjam (belum dikembalikan) -------------------------
$stmt = $conn->prepare(
    "SELECT p.kode_pinjam, p.tgl_pinjam, p.estimasi_pinjam, b.judul_buku, b.cover, b.kode_buku
     FROM peminjaman p
     JOIN buku b ON b.kode_buku = p.kode_buku
     LEFT JOIN pengembalian g ON g.kode_pinjam = p.kode_pinjam
     WHERE p.nim = :nim AND p.status = 'Dipinjam' AND g.kode_pinjam IS NULL
     ORDER BY p.estimasi_pinjam ASC"
);
$stmt->execute([':nim' => $nim]);
$dipinjam = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Riwayat pengembalian ---------------------------------------------------
$stmt = $conn->prepare(
    "SELECT p.kode_pinjam, p.tgl_pinjam, p.estimasi_pinjam,
            g.tgl_kembali, g.kondisi_buku, g.denda, g.status AS status_kembali
     FROM peminjaman p
     LEFT JOIN pengembalian g ON g.kode_pinjam = p.kode_pinjam
     WHERE p.nim = :nim AND g.kode_pinjam IS NOT NULL
     ORDER BY g.tgl_kembali DESC
     LIMIT 5"
);
$stmt->execute([':nim' => $nim]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Angka ringkas ----------------------------------------------------------
$totalDipinjam  = count($dipinjam);
$terlambat      = 0;
$jatuhTempo    = 0;
$proyeksiDenda  = 0;

foreach ($dipinjam as $d) {
    $sisa = $denda->sisaHari($d['estimasi_pinjam']);

    if ($sisa < 0) {
        $terlambat++;
    } elseif ($sisa <= 3) {
        $jatuhTempo++;
    }

    $proyeksiDenda += $denda->proyeksi($d['estimasi_pinjam'], KondisiBuku::Bagus)->total();
}

$stmt = $conn->prepare('SELECT COUNT(*) FROM peminjaman WHERE nim = :nim');
$stmt->execute([':nim' => $nim]);
$totalRiwayat = (int) $stmt->fetchColumn();

$unread = $reminder->unread('Anggota', $nim, 3);

$inisial = strtoupper(mb_substr((string) $mhs['nama'], 0, 1));
?>

<!-- ================= SAMBUTAN ================= -->
<section class="flex flex-wrap items-center justify-between gap-4">
  <div class="flex items-center gap-4">
    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-xl font-bold text-white shadow-sm">
      <?= htmlspecialchars($inisial) ?>
    </span>

    <div>
      <p class="text-muted">Selamat datang kembali,</p>
      <h1 class="text-title"><?= htmlspecialchars($mhs['nama']) ?></h1>
      <p class="text-muted mt-1">
        NIM <?= htmlspecialchars((string) $mhs['nim']) ?>
        &middot; <?= htmlspecialchars((string) $mhs['jurusan']) ?>
        &middot; <?= htmlspecialchars((string) $mhs['kelas']) ?>
      </p>
    </div>
  </div>

  <span class="<?= $mhs['status_mhs'] === 'Aktif' ? 'badge-safe' : 'badge-empty' ?>">
    <span class="h-1.5 w-1.5 rounded-full <?= $mhs['status_mhs'] === 'Aktif' ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
    <?= htmlspecialchars((string) $mhs['status_mhs']) ?>
  </span>
</section>

<!-- ================= STAT CARDS ================= -->
<section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

  <div class="stat-card">
    <span class="stat-icon bg-brand-50 text-brand-600">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
    </span>
    <div class="min-w-0">
      <p class="text-2xl font-bold leading-none text-slate-900"><?= $totalDipinjam ?></p>
      <p class="text-muted mt-1.5 truncate">Sedang Dipinjam</p>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-icon <?= $terlambat > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' ?>">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
      </svg>
    </span>
    <div class="min-w-0">
      <p class="text-2xl font-bold leading-none <?= $terlambat > 0 ? 'text-rose-600' : 'text-slate-900' ?>"><?= $terlambat ?></p>
      <p class="text-muted mt-1.5 truncate">Terlambat</p>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-icon bg-amber-50 text-amber-600">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </span>
    <div class="min-w-0">
      <p class="text-2xl font-bold leading-none text-slate-900"><?= $jatuhTempo ?></p>
      <p class="text-muted mt-1.5 truncate">Jatuh Tempo &le;3 Hari</p>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-icon <?= $proyeksiDenda > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' ?>">
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </span>
    <div class="min-w-0">
      <p class="truncate text-2xl font-bold leading-none <?= $proyeksiDenda > 0 ? 'text-rose-600' : 'text-slate-900' ?>">
        <?= $proyeksiDenda > 0 ? 'Rp' . number_format($proyeksiDenda, 0, ',', '.') : 'Rp0' ?>
      </p>
      <p class="text-muted mt-1.5 truncate">Proyeksi Denda</p>
    </div>
  </div>

</section>

<!-- ================= NOTIFIKASI ================= -->
<?php if ($unread): ?>
  <section class="mt-6">
    <div class="card-base overflow-hidden">
      <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50/60 px-5 py-3.5">
        <svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <h2 class="text-sm font-semibold text-slate-900">Pengingat</h2>
        <span class="badge-available ml-auto"><?= count($unread) ?> baru</span>
      </div>

      <ul class="divide-y divide-slate-100">
        <?php foreach ($unread as $n): ?>
          <li class="flex gap-3 px-5 py-4">
            <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full <?= $n['tipe'] === 'Kritis' ? 'bg-rose-500' : 'bg-amber-400' ?>"></span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($n['judul']) ?></p>
              <p class="text-muted mt-1 leading-relaxed"><?= htmlspecialchars($n['pesan']) ?></p>
              <p class="mt-1.5 text-xs text-slate-400"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
<?php endif; ?>

<!-- ================= BUKU SEDANG DIPINJAM ================= -->
<section class="mt-6">
  <div class="mb-4 flex items-end justify-between gap-4">
    <div>
      <h2 class="text-section">Sedang Dipinjam</h2>
      <p class="text-muted mt-0.5">Pantau tenggat pengembalian buku Anda</p>
    </div>
    <a href="home.php" class="btn-primary btn-sm shrink-0">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
      </svg>
      Cari Buku
    </a>
  </div>

  <?php if (!$dipinjam): ?>
    <div class="card-base flex flex-col items-center justify-center px-6 py-14 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </span>
      <h3 class="mt-4 text-base font-semibold text-slate-900">Tidak ada buku yang dipinjam</h3>
      <p class="text-muted mt-1 max-w-sm">Semua buku sudah dikembalikan. Silakan jelajahi katalog untuk menemukan buku baru.</p>
      <a href="home.php" class="btn-primary btn-sm mt-5">Jelajahi Katalog</a>
    </div>

  <?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach ($dipinjam as $d):
        $sisaHari   = $denda->sisaHari($d['estimasi_pinjam']);
        $proyeksi   = $denda->proyeksi($d['estimasi_pinjam'], KondisiBuku::Bagus);

        // Progress: 100% = tepat saat batas pengembalian.
        // date_create() hanya menerima SATU argumen, jadi strtotime() harus
        // dipakai di dalam date() lebih dulu.
        $tglPinjam  = date_create(date('Y-m-d', strtotime($d['tgl_pinjam'])));
        $tglBatas   = date_create(date('Y-m-d', strtotime($d['estimasi_pinjam'])));
        $durasi     = max(1, (int) date_diff($tglPinjam, $tglBatas)->days);
        $lewat      = max(0, -$sisaHari);
        $persen     = min(100, (int) round((($durasi + $lewat) / $durasi) * 100));

        if ($sisaHari < 0) {
            $kelasBadge = 'badge-late';
            $kelasBar   = 'bg-rose-500';
            $teks       = 'Terlambat ' . abs($sisaHari) . ' hari';
        } elseif ($sisaHari === 0) {
            $kelasBadge = 'badge-due';
            $kelasBar   = 'bg-amber-500';
            $teks       = 'Jatuh tempo hari ini';
        } elseif ($sisaHari <= 3) {
            $kelasBadge = 'badge-due';
            $kelasBar   = 'bg-amber-400';
            $teks       = $sisaHari . ' hari lagi';
        } else {
            $kelasBadge = 'badge-safe';
            $kelasBar   = 'bg-emerald-500';
            $teks       = $sisaHari . ' hari lagi';
        }
      ?>
        <article class="card-base card-hover flex gap-4 p-4">

          <img src="../Assets/uploads/<?= htmlspecialchars($d['cover']) ?>"
               alt="Cover <?= htmlspecialchars($d['judul_buku']) ?>"
               loading="lazy"
               class="h-28 w-20 shrink-0 rounded-xl object-cover" />

          <div class="flex min-w-0 flex-1 flex-col">
            <div class="flex items-start justify-between gap-3">
              <h3 class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900">
                <?= htmlspecialchars($d['judul_buku']) ?>
              </h3>
              <span class="<?= $kelasBadge ?> shrink-0"><?= $teks ?></span>
            </div>

            <p class="text-muted mt-1 text-xs">
              <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600"><?= htmlspecialchars($d['kode_pinjam']) ?></code>
            </p>

            <!-- Progress tenggat -->
            <div class="mt-auto pt-3">
              <div class="mb-1.5 flex items-center justify-between text-[11px]">
                <span class="text-slate-500">
                  Pinjam <?= date('d/m/Y', strtotime($d['tgl_pinjam'])) ?>
                </span>
                <span class="font-medium text-slate-700">
                  Batas <?= date('d/m/Y', strtotime($d['estimasi_pinjam'])) ?>
                </span>
              </div>

              <div class="progress-track" role="progressbar"
                   aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100"
                   aria-label="Progres tenggat pengembalian">
                <div class="progress-fill <?= $kelasBar ?>" style="width: <?= max(6, $persen) ?>%"></div>
              </div>

              <div class="mt-2 flex items-center justify-between gap-2">
                <p class="truncate text-[11px] text-slate-400">
                  Tarif <?= number_format($proyeksi->tarifPerHari, 0, ',', '.') ?>/hari
                </p>
                <?php if ($proyeksi->total() > 0): ?>
                  <p class="shrink-0 text-xs font-semibold text-rose-600">
                    Denda <?= htmlspecialchars($proyeksi->formatRupiah()) ?>
                  </p>
                <?php else: ?>
                  <p class="shrink-0 text-xs font-medium text-emerald-600">Tepat waktu</p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- ================= RIWAYAT PENGEMBALIAN ================= -->
<?php if ($riwayat): ?>
  <section class="mt-8">
    <div class="mb-4 flex items-end justify-between gap-4">
      <div>
        <h2 class="text-section">Pengembalian Terakhir</h2>
        <p class="text-muted mt-0.5"><?= $totalRiwayat ?> kali peminjaman sepanjang waktu</p>
      </div>
      <a href="history.php" class="btn-secondary btn-sm shrink-0">Lihat semua</a>
    </div>

    <!-- Desktop: tabel. Mobile: tetap tabel dengan overflow, bukan kartu,
         supaya konsisten dengan admin panel. -->
    <div class="card-base overflow-hidden">
      <div class="overflow-x-auto">
        <table class="table-base">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Dipinjam</th>
              <th>Dikembalikan</th>
              <th>Kondisi</th>
              <th class="text-right">Denda</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($riwayat as $r): ?>
              <tr>
                <td><code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600"><?= htmlspecialchars($r['kode_pinjam']) ?></code></td>
                <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                <td class="whitespace-nowrap"><?= $r['tgl_kembali'] ? date('d/m/Y', strtotime($r['tgl_kembali'])) : '-' ?></td>
                <td><?= htmlspecialchars((string) ($r['kondisi_buku'] ?? '-')) ?></td>
                <td class="whitespace-nowrap text-right font-semibold <?= (float) $r['denda'] > 0 ? 'text-rose-600' : 'text-slate-400' ?>">
                  <?= (float) $r['denda'] > 0 ? 'Rp' . number_format((float) $r['denda'], 0, ',', '.') : '-' ?>
                </td>
                <td>
                  <?php if ((float) $r['denda'] > 0): ?>
                    <span class="badge-late">Belum Lunas</span>
                  <?php else: ?>
                    <span class="badge-safe">Lunas</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php include 'footer.php'; ?>
