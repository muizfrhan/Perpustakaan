<?php
/**
 * Data Peminjaman.
 *
 * QUERY, URL FILTER (?filter= & ?page=), ID MODAL, dan nama fungsi
 * TIDAK BERUBAH. Yang diubah hanya markup + CSS.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'peminjaman';
include '../Layouts/header.php';

// Pagination logic
$limit = 5; // Jumlah data per halaman
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1); // Halaman saat ini
$offset = ($page - 1) * $limit;

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$whereClause = '';
if ($filter === 'dipinjam') {
  $whereClause = "WHERE NOT EXISTS (SELECT 1 FROM pengembalian WHERE pengembalian.kode_pinjam = peminjaman.kode_pinjam)";
} elseif ($filter === 'dikembalikan') {
  $whereClause = "WHERE EXISTS (SELECT 1 FROM pengembalian WHERE pengembalian.kode_pinjam = peminjaman.kode_pinjam)";
}

// Hitung total data
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM peminjaman $whereClause");
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = (int) $totalResult['total'];
$totalPages = (int) ceil($totalRows / $limit);

// Jaga agar halaman tidak melewati jumlah halaman yang ada.
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $limit;

// Ambil data sesuai halaman dan filter
$query = "
    SELECT peminjaman.kode_pinjam, anggota.nama AS nama_anggota, anggota.no_telp, buku.judul_buku,
    petugas.nama_petugas, peminjaman.tgl_pinjam, peminjaman.estimasi_pinjam,
    peminjaman.kondisi_buku_pinjam,
    IF(EXISTS (SELECT 1 FROM pengembalian WHERE pengembalian.kode_pinjam = peminjaman.kode_pinjam), 'Dikembalikan', 'Dipinjam') AS status
    FROM peminjaman
    INNER JOIN anggota ON peminjaman.nim = anggota.nim
    INNER JOIN buku ON peminjaman.kode_buku = buku.kode_buku
    INNER JOIN petugas ON peminjaman.id_petugas = petugas.id_petugas
    $whereClause
    ORDER BY peminjaman.tgl_pinjam DESC
    LIMIT :limit OFFSET :offset
";

$result = $conn->prepare($query);
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$filterMenu = [
    'semua'        => 'Semua',
    'dipinjam'     => 'Dipinjam',
    'dikembalikan' => 'Dikembalikan',
];

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
    <h1 class="text-title">Data Peminjaman</h1>
    <p class="text-muted mt-1">
      <?= number_format($totalRows, 0, ',', '.') ?> transaksi
      &middot; filter: <?= htmlspecialchars($filterMenu[$filter] ?? 'Semua') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="peminjamanFilterDropdown" class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        <?= htmlspecialchars($filterMenu[$filter] ?? 'Filter') ?>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="peminjamanFilterDropdown" data-dropdown class="dropdown right-0">
        <?php foreach ($filterMenu as $key => $label): ?>
          <a href="?filter=<?= $key ?>&page=1"
             class="dropdown-item <?= $filter === $key ? 'dropdown-item-active' : '' ?>">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
              <?php if ($filter === $key): ?>
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
      <input type="search" id="search" data-table-search="#peminjamanTable" autocomplete="off"
             class="field field-sm pl-10" placeholder="Cari peminjaman..." />
    </div>

    <!-- Tambah -->
    <button type="button" data-modal-open="tambahPeminjamanModal" class="btn-primary btn-sm">
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
    <table class="table-base" id="peminjamanTable">
      <thead>
        <tr>
          <th class="text-center">Kode</th>
          <th>Nama Anggota</th>
          <th>Judul Buku</th>
          <th>Petugas</th>
          <th>Tanggal</th>
          <th>Estimasi</th>
          <th>Kondisi</th>
          <th>Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="9" class="py-14 text-center text-slate-500">
              Belum ada data peminjaman pada filter ini.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)):
            $kode     = htmlspecialchars((string) $row['kode_pinjam']);
            $dipinjam = $row['status'] === 'Dipinjam';
            $terlambat = $dipinjam && strtotime(date('Y-m-d')) > strtotime($row['estimasi_pinjam']);
            ?>
          <tr>
            <td class="text-center">
              <code class="code-chip"><?= $kode ?></code>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= htmlspecialchars($row['nama_anggota']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['judul_buku']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['nama_petugas']) ?></td>
            <td class="whitespace-nowrap"><?= date('d-m-Y', strtotime($row['tgl_pinjam'])) ?></td>
            <td class="whitespace-nowrap">
              <span class="<?= $terlambat ? 'font-semibold text-rose-600' : '' ?>">
                <?= date('d-m-Y', strtotime($row['estimasi_pinjam'])) ?>
              </span>
              <?php if ($terlambat): ?>
                <span class="badge-late mt-1">Terlambat</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['kondisi_buku_pinjam'] === 'Bagus'): ?>
                <span class="badge-safe">Bagus</span>
              <?php else: ?>
                <span class="badge-due">Rusak</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($dipinjam): ?>
                <span class="badge-borrowed">Dipinjam</span>
              <?php else: ?>
                <span class="badge-available">Dikembalikan</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="inline-flex items-center justify-center gap-1.5">
                <button type="button" onclick="printData('<?= $kode ?>')" class="btn-secondary btn-sm">
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                  </svg>
                  Print
                </button>

                <?php if ($dipinjam): ?>
                  <a target="_blank" rel="noopener"
                     href="https://wa.me/<?= htmlspecialchars((string) preg_replace('/\D/', '', $row['no_telp'])) ?>?text=<?= rawurlencode('Halo ' . $row['nama_anggota'] . ', buku yang Anda pinjam sudah melewati estimasi pengembalian. Mohon dikembalikan segera.') ?>"
                     class="btn btn-sm border border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.174.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 016.988 2.896 9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488" />
                    </svg>
                    Chat
                  </a>
                <?php endif; ?>
              </div>
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
        <a href="?filter=<?= $filter ?>&page=<?= $page - 1; ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <?php if (count($rentangHalaman) > 1): ?>
          <div class="hidden items-center gap-1 sm:flex">
            <?php foreach ($rentangHalaman as $i): ?>
              <a href="?filter=<?= $filter ?>&page=<?= $i; ?>"
                 class="page-link <?= $i === $page ? 'page-link-active' : '' ?>"
                 <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="?filter=<?= $filter ?>&page=<?= $page + 1; ?>"
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
<div id="tambahPeminjamanModal" data-modal role="dialog" aria-modal="true" aria-labelledby="tambahPeminjamanLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-2xl">
    <div class="modal-header">
      <h2 class="modal-title" id="tambahPeminjamanLabel">Tambah Peminjaman Baru</h2>
      <button type="button" data-modal-close class="modal-close" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="modal-body" id="modalContent"></div>
  </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div id="editPeminjamanModal" data-modal role="dialog" aria-modal="true" aria-labelledby="editPeminjamanLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-2xl">
    <div class="modal-header">
      <h2 class="modal-title" id="editPeminjamanLabel">Edit Data Peminjaman</h2>
      <button type="button" data-modal-close class="modal-close" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="modal-body" id="editModalContent"></div>
  </div>
</div>

<script>
  /* loadEditForm - nama fungsi & endpoint DIJAGA. */
  function loadEditForm(kode_pinjam) {
    Pusaku.loadInto('#editModalContent', 'edit_peminjaman.php?kode_pinjam=' + encodeURIComponent(kode_pinjam),
      'Gagal memuat form edit peminjaman.');
  }

  /* searchTable - dipertahankan sebagai alias agar tidak merusak pemanggil lama. */
  function searchTable() {
    const input = document.getElementById('search');
    if (!input) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /* printData - nama fungsi DIJAGA. */
  function printData(kode_pinjam) {
    window.open('print_peminjaman.php?kode_pinjam=' + encodeURIComponent(kode_pinjam),
      '_blank', 'width=800,height=600');
  }

  /* filterTable - dipertahankan untuk pemanggil lama. */
  function filterTable() {
    const status = document.getElementById('filterStatus');
    if (!status) return;
    const params = new URLSearchParams(window.location.search);
    params.set('filter', status.value);
    params.set('page', 1);
    window.location.search = params.toString();
  }

  /**
   * Autocomplete generik untuk form peminjaman.
   * Endpoint lama (?query=) & selectAnggota/selectBuku DIJAGA.
   */
  function setupAutocompleteFor(inputId, panelId, endpoint, onPick, renderItem) {
    const input = document.getElementById(inputId);
    const panel = document.getElementById(panelId);
    if (!input || !panel) return;

    let timer = null;

    const tutup = () => {
      panel.classList.add('hidden');
      panel.innerHTML = '';
    };

    const cari = () => {
      const query = input.value.trim();
      if (query.length === 0) { tutup(); return; }

      fetch(endpoint + '?query=' + encodeURIComponent(query))
        .then((r) => r.json())
        .then((data) => {
          if (!data || data.length === 0) {
            panel.innerHTML = '<div class="autocomplete-empty">Tidak ada hasil yang ditemukan</div>';
          } else {
            panel.innerHTML = '';
            data.forEach((item) => {
              const row = document.createElement('div');
              row.className = 'autocomplete-item';
              row.innerHTML = renderItem(item);
              row.addEventListener('mousedown', (ev) => {
                ev.preventDefault();
                input.value = onPick(item);
                tutup();
                input.focus();
              });
              panel.appendChild(row);
            });
          }
          panel.classList.remove('hidden');
        })
        .catch(tutup);
    };

    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(cari, 220); // debounce
    });

    // Tutup saat klik di luar atau menekan Escape.
    document.addEventListener('click', (ev) => {
      if (!panel.contains(ev.target) && ev.target !== input) tutup();
    });
    input.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape') tutup();
    });
  }

  function setupAutocomplete() {
    setupAutocompleteFor('nim', 'search_results', 'search_anggota.php',
      (item) => item.nim,
      (item) => '<strong>' + item.nim + '</strong><span class="text-slate-400">-</span>' + item.nama);
  }

  function setupAutocompleteBuku() {
    setupAutocompleteFor('kode_buku', 'search_results_buku', 'search_buku.php',
      (item) => item.kode_buku,
      (item) => '<strong>' + item.kode_buku + '</strong><span class="text-slate-400">-</span>' + item.judul_buku);
  }

  /* selectAnggota / selectBuku - nama fungsi DIJAGA untuk kompatibilitas. */
  window.selectAnggota = function (nim) {
    const el = document.getElementById('nim');
    if (el) el.value = nim;
    document.getElementById('search_results')?.classList.add('hidden');
  };
  window.selectBuku = function (kodeBuku) {
    const el = document.getElementById('kode_buku');
    if (el) el.value = kodeBuku;
    document.getElementById('search_results_buku')?.classList.add('hidden');
  };

  /* Muat form tambah saat modal dibuka (pengganti event show.bs.modal). */
  document.getElementById('tambahPeminjamanModal').addEventListener('modal:open', function () {
    Pusaku.loadInto('#modalContent', 'add_peminjaman.php', 'Gagal memuat form peminjaman.')
      .then(() => {
        setupAutocomplete();
        setupAutocompleteBuku();
      });
  });
</script>

<?php include '../Layouts/footer.php'; ?>
