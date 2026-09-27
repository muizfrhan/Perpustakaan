<?php
/**
 * Detail Buku (fragment) - panel User.
 *
 * CATATAN: halaman katalog User (home.php) sebenarnya mengambil detail
 * lewat User/get_detail_buku.php (JSON) lalu merendernya di sisi klien.
 * File ini dipertahankan sebagai fallback/fragmen server-side.
 *
 * Tidak ada <html>/<body> - isinya langsung ditampilkan di dalam modal.
 */
require_once '../Config/koneksi.php';
require_once __DIR__ . '/../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_user();


// Mengambil ID buku dari query string
$kode_buku = isset($_GET['kode_buku']) ? $_GET['kode_buku'] : '';

if (empty($kode_buku)) {
    echo '<div class="alert-danger">ID Buku tidak valid.</div>';
    return;
}

// Query untuk mengambil data buku berdasarkan kode_buku
$query = $conn->prepare("SELECT * FROM buku WHERE kode_buku = :kode_buku");
$query->bindValue(':kode_buku', $kode_buku, PDO::PARAM_STR);
$query->execute();

if ($query->rowCount() === 0) {
    echo '<div class="alert-danger">Buku tidak ditemukan.</div>';
    return;
}

// Ambil data buku
$buku = $query->fetch(PDO::FETCH_ASSOC);

$statusBuku = [
    'Tersedia' => ['badge-available', 'Tersedia'],
    'Dipinjam' => ['badge-borrowed', 'Dipinjam'],
    'Kosong'   => ['badge-empty', 'Kosong'],
];
$meta = $statusBuku[$buku['status']] ?? ['badge-neutral', $buku['status'] ?: '-'];

$info = [
    'Kategori'       => $buku['kategori'],
    'Pengarang'      => $buku['pengarang'],
    'Penerbit'       => $buku['penerbit'],
    'Tanggal Terbit' => $buku['tanggal_terbit'],
    'Jumlah Halaman' => $buku['jumlah_halaman'] . ' halaman',
    'Bahasa'         => $buku['bahasa'],
    'Stok'           => $buku['stok'] . ' eksemplar',
];
?>

<div class="flex flex-col gap-6 sm:flex-row">

  <!-- Cover -->
  <div class="w-full shrink-0 sm:w-40">
    <?php if (!empty($buku['cover'])): ?>
      <img src="../Assets/uploads/<?= htmlspecialchars($buku['cover']); ?>"
           alt="Cover <?= htmlspecialchars($buku['judul_buku']); ?>"
           class="w-full rounded-2xl border border-slate-200 object-cover shadow-card" />
    <?php else: ?>
      <div class="flex aspect-[3/4] w-full flex-col items-center justify-center gap-2
                  rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-slate-400">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <span class="text-xs font-medium">Tanpa cover</span>
      </div>
    <?php endif; ?>

    <div class="mt-3 flex justify-center">
      <span class="<?= $meta[0] ?>"><?= htmlspecialchars($meta[1]); ?></span>
    </div>
  </div>

  <!-- Informasi -->
  <div class="min-w-0 flex-1">
    <code class="code-chip"><?= htmlspecialchars((string) $buku['kode_buku']); ?></code>
    <h3 class="mt-2 text-xl font-bold leading-snug tracking-tight text-slate-900">
      <?= htmlspecialchars($buku['judul_buku']); ?>
    </h3>

    <dl class="mt-4">
      <?php foreach ($info as $label => $value): ?>
        <div class="dl-row">
          <dt class="dl-term"><?= htmlspecialchars((string) $label); ?></dt>
          <dd class="dl-desc text-right"><?= htmlspecialchars((string) ($value !== '' ? $value : '-')); ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</div>

<!-- Deskripsi -->
<div class="mt-6 border-t border-slate-200 pt-5">
  <h4 class="text-label">Deskripsi Buku</h4>
  <?php if (trim((string) $buku['deskripsi_buku']) !== ''): ?>
    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">
      <?= htmlspecialchars((string) $buku['deskripsi_buku']); ?>
    </p>
  <?php else: ?>
    <p class="mt-2 text-sm italic text-slate-400">Belum ada deskripsi untuk buku ini.</p>
  <?php endif; ?>
</div>
