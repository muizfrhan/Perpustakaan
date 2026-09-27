<?php
/**
 * Data Buku (Panel Owner - hanya baca).
 *
 * QUERY, URL FILTER (?filter= & ?page=), ID MODAL (#detailBukuModal),
 * dan nama fungsi TIDAK BERUBAH.
 * Yang diubah hanya markup + CSS.
 *
 * Owner bersifat read-only: tidak ada affordance tambah/edit/hapus.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'buku';
include '../Layouts/header.php';

// Variabel filter
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1); // Halaman saat ini
$offset = ($page - 1) * $limit;

// Query untuk menghitung total data dengan filter
$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM buku WHERE (:filterStatus = 'all' OR status = :filterStatus)");
$totalQuery->bindValue(':filterStatus', $filterStatus, PDO::PARAM_STR);
$totalQuery->execute();
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = (int) $totalResult['total'];
$totalPages = (int) ceil($totalRows / $limit);

// Jaga agar halaman tidak melewati jumlah halaman yang ada.
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $limit;

// Query untuk mengambil data buku dengan filter
$result = $conn->prepare("SELECT * FROM buku WHERE (:filterStatus = 'all' OR status = :filterStatus) LIMIT :limit OFFSET :offset");
$result->bindValue(':filterStatus', $filterStatus, PDO::PARAM_STR);
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$filterMenu = [
    'all'      => 'Semua Buku',
    'Tersedia' => 'Tersedia',
    'Dipinjam' => 'Dipinjam',
    'Kosong'   => 'Kosong',
];

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
    <h1 class="text-title">Data Buku</h1>
    <p class="text-muted mt-1">
      <?= number_format($totalRows, 0, ',', '.') ?> judul terdaftar
      &middot; filter: <?= htmlspecialchars($filterMenu[$filterStatus] ?? 'Semua Buku') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="filterDropdown" class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        <?= htmlspecialchars($filterMenu[$filterStatus] ?? 'Filter') ?>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="filterDropdown" data-dropdown class="dropdown hidden right-0">
        <?php foreach ($filterMenu as $key => $label): ?>
          <a href="?filter=<?= urlencode($key) ?>&page=1"
             class="dropdown-item <?= $filterStatus === $key ? 'dropdown-item-active' : '' ?>">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
              <?php if ($filterStatus === $key): ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              <?php else: ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75L12 13.5l3.75-3.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              <?php endif; ?>
            </svg>
            <?= htmlspecialchars($label) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Pencarian -->
    <div class="relative w-full sm:w-56">
      <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
           fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
      </svg>
      <input type="search" id="search" data-table-search="#bukuTable" autocomplete="off"
             class="field field-sm pl-10" placeholder="Cari Buku..." />
    </div>
  </div>
</section>

<!-- ================= TABEL ================= -->
<section class="card-base mt-6 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="table-base" id="bukuTable">
      <thead>
        <tr>
          <th class="text-center">Kode Buku</th>
          <th>Judul Buku</th>
          <th>Pengarang</th>
          <!-- <th>Penerbit</th> -->
          <th>Tanggal Terbit</th>
          <!-- <th>Jumlah Halaman</th> -->
          <!-- <th>Bahasa</th> -->
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="5" class="py-14 text-center text-slate-500">
              Belum ada buku yang cocok dengan filter ini.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)):
            $kode = htmlspecialchars((string) $row['kode_buku']);
            ?>
          <tr>
            <td class="text-center">
              <code class="code-chip"><?= $kode ?></code>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= htmlspecialchars($row['judul_buku']); ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['pengarang']); ?></td>
            <!-- <td><?= htmlspecialchars($row['penerbit']) ?></td> -->
            <td class="whitespace-nowrap"><?= htmlspecialchars((string) $row['tanggal_terbit']); ?></td>
            <!-- <td><?= htmlspecialchars($row['jumlah_halaman']) ?></td> -->
            <!-- <td><?= htmlspecialchars($row['bahasa']) ?></td> -->
            <td class="text-center">
              <button type="button" data-modal-open="detailBukuModal"
                      onclick="loadDetailForm('<?= $kode ?>')"
                      class="btn-secondary btn-sm">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                Detail
              </button>
            </td>
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
        <a href="?page=<?= $page - 1; ?>&filter=<?= urlencode($filterStatus) ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <?php if (count($rentangHalaman) > 1): ?>
          <div class="hidden items-center gap-1 sm:flex">
            <?php foreach ($rentangHalaman as $i): ?>
              <a href="?page=<?= $i; ?>&filter=<?= urlencode($filterStatus) ?>"
                 class="page-link <?= $i === $page ? 'page-link-active' : '' ?>"
                 <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="?page=<?= $page + 1; ?>&filter=<?= urlencode($filterStatus) ?>"
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

<!-- ================= MODAL DETAIL ================= -->
<div id="detailBukuModal" data-modal role="dialog" aria-modal="true" aria-labelledby="detailBukuLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-2xl">
    <div class="modal-header">
      <h2 class="modal-title" id="detailBukuLabel">Detail Buku</h2>
      <button type="button" data-modal-close class="modal-close" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="modal-body" id="detailModalContent">
      <!-- Data Buku akan dimuat di sini menggunakan AJAX -->
    </div>
  </div>
</div>

<script>
  /* loadDetailForm - nama fungsi & endpoint DIJAGA. */
  function loadDetailForm(kode_buku) {
    Pusaku.loadInto('#detailModalContent', 'detail_buku.php?kode_buku=' + encodeURIComponent(kode_buku),
      'Gagal memuat detail buku.');
  }

  /* Fitur Searching - dipertahankan sebagai alias agar tidak rusak pemanggil lama. */
  function searchTable() {
    const input = document.getElementById('search');
    if (!input) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /* applyFilter - dipertahankan; dipakai bila ada tombol filter tambahan. */
  function applyFilter(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('filter', status);
    url.searchParams.set('page', 1); // Reset ke halaman 1 setiap kali filter diubah
    window.location.href = url.toString();
  }
</script>

<?php include '../Layouts/footer.php'; ?>
