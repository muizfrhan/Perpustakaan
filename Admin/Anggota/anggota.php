<?php
/**
 * Data Anggota
 *
 * QUERY, URL FILTER, ID MODAL, dan nama fungsi TIDAK BERUBAH.
 * Yang diubah hanya markup + CSS.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'anggota';
include '../Layouts/header.php';

// Get the filter for member status
$memberFilter = isset($_GET['member_filter']) ? $_GET['member_filter'] : 'semua'; // Default is 'semua' (all)
$whereClause = '';
if ($memberFilter === 'aktif') {
  $whereClause = "WHERE anggota.status_mhs = 'Aktif'";
} elseif ($memberFilter === 'tidak_aktif') {
  $whereClause = "WHERE anggota.status_mhs = 'Tidak Aktif'";
}

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page - 1) * $limit;

// Hitung total data
$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM anggota $whereClause");
$totalQuery->execute();
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Ambil data sesuai halaman dan filter status anggota dan urutan abjad
$result = $conn->prepare("
    SELECT * FROM anggota
    $whereClause
    ORDER BY nama ASC
    LIMIT :limit OFFSET :offset
");

$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$filterAktif = [
    'semua'       => 'Semua Anggota',
    'aktif'       => 'Aktif',
    'tidak_aktif' => 'Tidak Aktif',
];
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Data Anggota</h1>
    <p class="text-muted mt-1">
      <?= number_format((int) $totalRows, 0, ',', '.') ?> anggota terdaftar
      &middot; filter: <?= htmlspecialchars($filterAktif[$memberFilter] ?? 'Semua Anggota') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="memberFilterDropdown"
              class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        Filter
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="memberFilterDropdown" data-dropdown
           class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-lift">
        <?php foreach (['semua' => 'Semua', 'aktif' => 'Aktif', 'tidak_aktif' => 'Tidak Aktif'] as $key => $label): ?>
          <a href="?member_filter=<?= $key ?>"
             class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition
                    <?= $memberFilter === $key ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50' ?>">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="<?= $memberFilter === $key ? '2.2' : '1.7' ?>" stroke="currentColor">
              <?php if ($memberFilter === $key): ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              <?php else: ?>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9.75L12 13.5l3.75-3.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              <?php endif; ?>
            </svg>
            <?= $label ?>
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
      <input type="search" id="search" data-table-search="#anggotaTable" autocomplete="off"
             class="field field-sm pl-10" placeholder="Cari anggota..." />
    </div>

    <!-- Tambah -->
    <button type="button" data-modal-open="tambahAnggotaModal" class="btn-primary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
      </svg>
      Tambah
    </button>
  </div>
</section>

<!-- ================= TABEL ================= -->
<section class="card-base mt-6 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="table-base" id="anggotaTable">
      <thead>
        <tr>
          <th class="text-center">NIM</th>
          <th>Nama</th>
          <th>No. Telp</th>
          <th>Jenis Kelamin</th>
          <th>Jurusan</th>
          <th>Kelas</th>
          <th>Tanggal Lahir</th>
          <th>Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="9" class="py-12 text-center text-slate-500">
              Tidak ada anggota yang cocok dengan filter ini.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td class="text-center">
              <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-600"><?= htmlspecialchars((string) $row['nim']) ?></code>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= htmlspecialchars($row['nama']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars((string) ($row['no_telp'] ?: '-')) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['jurusan']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['kelas']) ?></td>
            <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tgl_lahir'])) ?></td>
            <td>
              <?php if ($row['status_mhs'] == 'Aktif'): ?>
                <span class="badge-safe">Aktif</span>
              <?php else: ?>
                <span class="badge-empty">Tidak Aktif</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <button type="button" data-modal-open="editAnggotaModal"
                      onclick="loadEditForm('<?= htmlspecialchars((string) $row['nim']) ?>')"
                      class="btn-secondary btn-sm">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                </svg>
                Edit
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="flex items-center justify-between gap-4 border-t border-slate-200 px-5 py-3.5">
      <p class="text-muted">
        Halaman <span class="font-semibold text-slate-700"><?= $page ?></span> dari <?= (int) $totalPages ?>
      </p>

      <div class="flex items-center gap-1.5">
        <a href="?page=<?= $page - 1; ?>&member_filter=<?= $memberFilter ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <a href="?page=<?= $page + 1; ?>&member_filter=<?= $memberFilter ?>"
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

<!-- ================= MODAL TAMBAH ================= -->
<div id="tambahAnggotaModal" data-modal role="dialog" aria-modal="true" aria-labelledby="tambahAnggotaLabel"
     class="fixed inset-0 z-50 hidden items-end justify-center sm:items-center sm:p-6">
  <div data-modal-backdrop class="absolute inset-0 bg-slate-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>
  <div class="relative flex max-h-[92vh] w-full max-w-2xl translate-y-6 flex-col overflow-hidden rounded-t-3xl bg-white shadow-lift transition-all duration-300 sm:translate-y-0 sm:rounded-3xl">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
      <h2 class="text-base font-semibold text-slate-900" id="tambahAnggotaLabel">Tambah Anggota Baru</h2>
      <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="overflow-y-auto" id="modalContent"></div>
  </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div id="editAnggotaModal" data-modal role="dialog" aria-modal="true" aria-labelledby="editAnggotaLabel"
     class="fixed inset-0 z-50 hidden items-end justify-center sm:items-center sm:p-6">
  <div data-modal-backdrop class="absolute inset-0 bg-slate-900/50 opacity-0 backdrop-blur-sm transition-opacity duration-300"></div>
  <div class="relative flex max-h-[92vh] w-full max-w-2xl translate-y-6 flex-col overflow-hidden rounded-t-3xl bg-white shadow-lift transition-all duration-300 sm:translate-y-0 sm:rounded-3xl">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
      <h2 class="text-base font-semibold text-slate-900" id="editAnggotaLabel">Edit Anggota</h2>
      <button type="button" data-modal-close class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="overflow-y-auto" id="editModalContent"></div>
  </div>
</div>

<script>
  /* loadEditForm - nama fungsi & endpoint DIJAGA. */
  function loadEditForm(nim) {
    const box = document.getElementById('editModalContent');
    if (!box) return;

    box.innerHTML = '<div class="flex items-center justify-center gap-3 px-6 py-20 text-slate-400">'
      + '<svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">'
      + '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>'
      + '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>'
      + '<span class="text-sm">Memuat data...</span></div>';

    fetch('edit_anggota.php?nim=' + encodeURIComponent(nim))
      .then((r) => r.text())
      .then((html) => { box.innerHTML = html; })
      .catch(() => {
        box.innerHTML = '<p class="px-6 py-16 text-center text-sm text-slate-500">Gagal memuat data anggota.</p>';
      });
  }

  /* searchTable - dipertahankan sebagai alias agar tidak merusak pemanggil lama. */
  function searchTable() {
    const input = document.getElementById('search');
    if (!input) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }
</script>

<?php include '../Layouts/footer.php'; ?>
