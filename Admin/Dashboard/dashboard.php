<?php
/**
 * Dashboard Admin
 *
 * QUERY TIDAK BERUBAH dari versi lama. Yang berubah hanya markup + CSS.
 * Semua nilai statistik memakai rowCount() seperti sebelumnya.
 */
session_start();
require_once '../../Config/koneksi.php';

// Pastikan sesi untuk ID petugas (cek versi lama dipertahankan)
if (!isset($_SESSION['id_petugas'])) {
    header('Location: ../../login.php');
    exit();
}

$id_petugas = $_SESSION['id_petugas'];

// Query untuk mendapatkan jumlah anggota, buku, peminjaman, dan pengembalian
$anggotaResult      = $conn->query("SELECT * FROM anggota");
$bukuResult         = $conn->query("SELECT * FROM buku");
$peminjamanResult   = $conn->query("SELECT * FROM peminjaman");
$pengembalianResult = $conn->query("SELECT * FROM pengembalian");

// Query untuk mendapatkan nama petugas
$query = "SELECT nama_petugas, profil_gambar FROM petugas WHERE id_petugas = :id_petugas";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_petugas', $id_petugas, PDO::PARAM_INT);
$stmt->execute();

$nama_petugas = "Tidak Diketahui";
$profil_gambar = '';

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nama_petugas = $row['nama_petugas'];
    $profil_gambar = $row['profil_gambar'];
}

$tanggal_hari_ini = date('jS F Y');

// Query untuk peminjaman yang perlu dikembalikan
$queryPinjamKembali = "
    SELECT p.kode_pinjam, a.nama, p.kode_buku, p.estimasi_pinjam
    FROM peminjaman p
    JOIN anggota a ON p.nim = a.nim
    WHERE p.estimasi_pinjam < CURDATE() AND p.status = 'Dipinjam'
";
$stmtPinjamKembali = $conn->query($queryPinjamKembali);

// Query untuk pengembalian dengan status belum lunas
$queryBelumLunas = "
    SELECT pk.kode_kembali, a.nama, pk.denda, pk.pembayaran
    FROM pengembalian pk
    JOIN peminjaman p ON pk.kode_pinjam = p.kode_pinjam
    JOIN anggota a ON p.nim = a.nim
    WHERE pk.status = 'Belum Lunas'
";
$stmtBelumLunas = $conn->query($queryBelumLunas);

$namaUser  = $nama_petugas;
$fotoUser  = $profil_gambar;
$menuAktif = 'dashboard';
include '../Layouts/header.php';
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Dashboard</h1>
    <p class="text-muted mt-1">Ringkasan aktivitas perpustakaan &middot; <?= $tanggal_hari_ini ?></p>
  </div>

  <div class="flex items-center gap-3">
    <a href="../../logout.php"
       data-confirm="Yakin ingin logout dari panel admin?"
       class="btn-secondary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
      </svg>
      Keluar
    </a>
  </div>
</section>

<!-- ================= STAT CARDS ================= -->
<section class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">

  <?php
  $kartu = [
      ['label' => 'Anggota', 'nilai' => $anggotaResult->rowCount(), 'href' => '../Anggota/anggota.php',
       'chip' => 'bg-brand-50 text-brand-600', 'ikon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
      ['label' => 'Buku', 'nilai' => $bukuResult->rowCount(), 'href' => '../Buku/buku.php',
       'chip' => 'bg-emerald-50 text-emerald-600', 'ikon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
      ['label' => 'Peminjaman', 'nilai' => $peminjamanResult->rowCount(), 'href' => '../Peminjaman/peminjaman.php',
       'chip' => 'bg-amber-50 text-amber-600', 'ikon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 006 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
      ['label' => 'Pengembalian', 'nilai' => $pengembalianResult->rowCount(), 'href' => '../Pengembalian/pengembalian.php',
       'chip' => 'bg-violet-50 text-violet-600', 'ikon' => 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3m-9-6h.01'],
  ];

  foreach ($kartu as $k): ?>
    <a href="<?= $k['href'] ?>" class="stat-card card-hover">
      <span class="stat-icon <?= $k['chip'] ?>">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="<?= $k['ikon'] ?>" />
        </svg>
      </span>
      <div class="min-w-0">
        <p class="text-2xl font-bold leading-none text-slate-900"><?= $k['nilai'] ?></p>
        <p class="text-muted mt-1.5 truncate"><?= $k['label'] ?></p>
      </div>
    </a>
  <?php endforeach; ?>

</section>

<!-- ================= KALENDER + PANDAI ================= -->
<section class="mt-6 grid gap-6 xl:grid-cols-2">

  <!-- Kalender -->
  <div class="card-base p-5">
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-section" id="month-year">&nbsp;</h2>
      <div class="flex gap-1.5">
        <button type="button" id="prev" aria-label="Bulan sebelumnya"
                class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <button type="button" id="next" aria-label="Bulan berikutnya"
                class="rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </button>
      </div>
    </div>

    <div id="calendar-grid" class="grid grid-cols-7 gap-1.5 text-center"></div>
  </div>

  <!-- Perlu dikembalikan -->
  <div class="card-base overflow-hidden">
    <div class="flex items-center gap-3 border-b border-slate-200 bg-rose-50/50 px-5 py-4">
      <span class="stat-icon bg-rose-100 text-rose-600">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </span>
      <div>
        <h2 class="text-sm font-semibold text-slate-900">Perlu Dikembalikan</h2>
        <p class="text-muted mt-0.5">Peminjaman yang sudah melewati batas waktu</p>
      </div>
      <span class="badge-late ml-auto"><?= $stmtPinjamKembali->rowCount() ?></span>
    </div>

    <div class="divide-y divide-slate-100">
      <?php if ($stmtPinjamKembali->rowCount() > 0): ?>
        <?php while ($row = $stmtPinjamKembali->fetch(PDO::FETCH_ASSOC)): ?>
          <div class="flex items-center justify-between gap-4 px-5 py-3.5 transition hover:bg-slate-50">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-900">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600">
                  <?= htmlspecialchars($row['kode_pinjam']) ?>
                </code>
                <?= htmlspecialchars($row['nama']) ?>
              </p>
              <p class="text-muted mt-1 text-xs">
                Estimasi <?= date('d/m/Y', strtotime($row['estimasi_pinjam'])) ?>
              </p>
            </div>
            <a href="../Peminjaman/peminjaman.php" class="btn-secondary btn-sm shrink-0">Detail</a>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="px-5 py-10 text-center text-sm text-slate-500">
          Tidak ada peminjaman yang perlu dikembalikan.
        </p>
      <?php endif; ?>
    </div>
  </div>

</section>

<!-- ================= BELUM LUNAS ================= -->
<section class="card-base mt-6 overflow-hidden">
  <div class="flex items-center gap-3 border-b border-slate-200 bg-amber-50/50 px-5 py-4">
    <span class="stat-icon bg-amber-100 text-amber-600">
      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </span>
    <div>
      <h2 class="text-sm font-semibold text-slate-900">Denda Belum Lunas</h2>
      <p class="text-muted mt-0.5">Pengembalian yang dendanya belum dibayar</p>
    </div>
    <span class="badge-due ml-auto"><?= $stmtBelumLunas->rowCount() ?></span>
  </div>

  <?php if ($stmtBelumLunas->rowCount() > 0): ?>
    <div class="overflow-x-auto">
      <table class="table-base">
        <thead>
          <tr>
            <th>Kode Kembali</th>
            <th>Nama Anggota</th>
            <th class="text-right">Denda</th>
            <th>Pembayaran</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $stmtBelumLunas->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
              <td>
                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600">
                  <?= htmlspecialchars($row['kode_kembali']) ?>
                </code>
              </td>
              <td class="font-medium text-slate-900"><?= htmlspecialchars($row['nama']) ?></td>
              <td class="whitespace-nowrap text-right font-semibold text-rose-600">
                Rp<?= number_format((float) $row['denda'], 2, ',', '.') ?>
              </td>
              <td><span class="badge-info"><?= htmlspecialchars((string) ($row['pembayaran'] ?? '-')) ?></span></td>
              <td class="text-right">
                <a href="../Pengembalian/pengembalian.php" class="btn-secondary btn-sm">Proses</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="px-5 py-10 text-center text-sm text-slate-500">
      Semua denda sudah lunas.
    </p>
  <?php endif; ?>
</section>

<script>
  /* Kalender ringan, tanpa library. */
  (function () {
    const grid  = document.getElementById('calendar-grid');
    const label = document.getElementById('month-year');
    if (!grid || !label) return;

    const bulan = ["Januari","Februari","Maret","April","Mei","Juni",
                   "Juli","Agustus","September","Oktober","November","Desember"];
    const hari  = ["Min","Sen","Sel","Rab","Kam","Jum","Sab"];

    let sekarang = new Date();

    function gambar() {
      grid.innerHTML = '';
      const t = sekarang.getMonth();
      const y = sekarang.getFullYear();
      label.textContent = bulan[t] + ' ' + y;

      hari.forEach((h) => {
        const el = document.createElement('div');
        el.className = 'text-[11px] font-semibold uppercase tracking-wider text-slate-400 py-1';
        el.textContent = h;
        grid.appendChild(el);
      });

      // Sel kosong sebelum tanggal 1
      for (let i = 0; i < new Date(y, t, 1).getDay(); i++) {
        grid.appendChild(document.createElement('div'));
      }

      const total = new Date(y, t + 1, 0).getDate();
      const hariIni = new Date();

      for (let d = 1; d <= total; d++) {
        const el = document.createElement('div');
        const iniBulanIni = t === hariIni.getMonth() && y === hariIni.getFullYear();
        el.textContent = d;
        el.className = 'rounded-lg py-2 text-sm transition ' + (
          d === hariIni.getDate() && iniBulanIni
            ? 'bg-brand-600 font-bold text-white'
            : 'text-slate-600 hover:bg-slate-100'
        );
        grid.appendChild(el);
      }
    }

    document.getElementById('prev').addEventListener('click', () => {
      sekarang.setMonth(sekarang.getMonth() - 1);
      gambar();
    });

    document.getElementById('next').addEventListener('click', () => {
      sekarang.setMonth(sekarang.getMonth() + 1);
      gambar();
    });

    gambar();
  })();
</script>

<?php include '../Layouts/footer.php'; ?>
