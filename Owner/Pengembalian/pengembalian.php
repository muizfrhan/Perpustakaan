<?php
/**
 * Data Pengembalian (Panel Owner - hanya baca & cetak).
 *
 * QUERY, URL FILTER (?filter= & ?page=), dan nama fungsi
 * TIDAK BERUBAH. Yang diubah hanya markup + CSS.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'pengembalian';
include '../Layouts/header.php';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1); // Halaman saat ini
$offset = ($page - 1) * $limit;

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua'; // Menangani filter

$whereClause = '';
if ($filter === 'belum_lunas') {
  $whereClause = "WHERE pengembalian.status = 'Belum Lunas'";
} elseif ($filter === 'lunas') {
  $whereClause = "WHERE pengembalian.status = 'Lunas'";
}

// Hitung total data
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM pengembalian $whereClause");
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = (int) $totalResult['total'];
$totalPages = (int) ceil($totalRows / $limit);

// Jaga agar halaman tidak melewati jumlah halaman yang ada.
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $limit;

// Update query untuk menambahkan kondisi WHERE berdasarkan filter
$result = $conn->prepare("
    SELECT pengembalian.kode_kembali, pengembalian.tgl_kembali, pengembalian.kode_pinjam,
           pengembalian.kondisi_buku, pengembalian.denda, pengembalian.status,
           pengembalian.pembayaran, anggota.nama AS nama_anggota,
           anggota.no_telp, buku.judul_buku
    FROM pengembalian
    JOIN peminjaman ON pengembalian.kode_pinjam = peminjaman.kode_pinjam
    JOIN anggota ON peminjaman.nim = anggota.nim
    JOIN buku ON peminjaman.kode_buku = buku.kode_buku
    $whereClause
    ORDER BY pengembalian.tgl_kembali DESC
    LIMIT :limit OFFSET :offset
");

$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$filterMenu = [
    'semua'       => 'Semua',
    'belum_lunas' => 'Belum Lunas',
    'lunas'       => 'Lunas',
];

/** Peta kondisi buku -> kelas badge. */
$kondisiBuku = [
    'Bagus' => 'badge-safe',
    'Rusak' => 'badge-due',
];

/** Peta status pengembalian -> kelas badge. */
$statusKembali = [
    'Lunas'       => 'badge-available',
    'Belum Lunas' => 'badge-late',
];

/** Peta metode pembayaran -> kelas badge. */
$metodeBayar = [
    'Tidak Ada' => ['badge-neutral', 'Tidak Ada'],
    'Kes'       => ['badge-borrowed', 'Cash'],
    'Transfer'  => ['badge-info', 'Transfer'],
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
    <h1 class="text-title">Data Pengembalian</h1>
    <p class="text-muted mt-1">
      <?= number_format($totalRows, 0, ',', '.') ?> transaksi pengembalian
      &middot; filter: <?= htmlspecialchars($filterMenu[$filter] ?? 'Semua') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="filterDropdown" class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        <?= htmlspecialchars($filterMenu[$filter] ?? 'Filter') ?>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="filterDropdown" data-dropdown class="dropdown hidden right-0">
        <?php foreach ($filterMenu as $key => $label): ?>
          <a href="?filter=<?= urlencode($key) ?>&page=1"
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
      <input type="search" id="search" data-table-search="#pengembalianTable" autocomplete="off"
             class="field field-sm pl-10" placeholder="Cari Pengembalian..." />
    </div>

    <!-- Cetak Semua -->
    <button type="button" onclick="printAll()" class="btn-primary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
      </svg>
      Cetak Semua
    </button>
  </div>
</section>

<!-- ================= TABEL ================= -->
<section class="card-base mt-6 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="table-base" id="pengembalianTable">
      <thead>
        <tr>
          <th class="text-center">Kode</th>
          <th>Nama Anggota</th>
          <th>Judul Buku</th>
          <th>Tgl.Kembali</th>
          <th>Kondisi</th>
          <th>Denda</th>
          <th>Status</th>
          <th>Pembayaran</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="9" class="py-14 text-center text-slate-500">
              Belum ada data pengembalian pada filter ini.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)):
            $kode     = htmlspecialchars((string) $row['kode_kembali']);
            $kondisi  = $kondisiBuku[$row['kondisi_buku']] ?? 'badge-late';
            $status   = $statusKembali[$row['status']] ?? 'badge-neutral';
            $bayar    = $metodeBayar[$row['pembayaran']] ?? ['badge-neutral', (string) ($row['pembayaran'] ?: '-')];
            ?>
          <tr>
            <td class="text-center">
              <code class="code-chip"><?= $kode ?></code>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= htmlspecialchars($row['nama_anggota']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['judul_buku']) ?></td>
            <td class="whitespace-nowrap"><?= date('d-m-Y', strtotime($row['tgl_kembali'])) ?></td>
            <td>
              <span class="<?= $kondisi ?>"><?= htmlspecialchars((string) $row['kondisi_buku']) ?></span>
            </td>
            <td class="whitespace-nowrap">Rp<?= number_format((float) $row['denda'], 2, ',', '.') ?></td>
            <td>
              <span class="<?= $status ?>"><?= htmlspecialchars((string) $row['status']) ?></span>
            </td>
            <td>
              <span class="<?= $bayar[0] ?>"><?= htmlspecialchars($bayar[1]) ?></span>
            </td>
            <td class="text-center">
              <button type="button" onclick="printData('<?= $kode ?>')" class="btn-secondary btn-sm">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                </svg>
                Print
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
        <a href="?filter=<?= urlencode($filter) ?>&page=<?= $page - 1; ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <?php if (count($rentangHalaman) > 1): ?>
          <div class="hidden items-center gap-1 sm:flex">
            <?php foreach ($rentangHalaman as $i): ?>
              <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i; ?>"
                 class="page-link <?= $i === $page ? 'page-link-active' : '' ?>"
                 <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="?filter=<?= urlencode($filter) ?>&page=<?= $page + 1; ?>"
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

<script>
  /* Fitur Searching - dipertahankan sebagai alias agar tidak rusak pemanggil lama. */
  function searchTable() {
    const input = document.getElementById('search');
    if (!input) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /* Fitur Print - nama fungsi DIJAGA. */
  function printData(kode_kembali) {
    window.open('print_pengembalian.php?kode_kembali=' + encodeURIComponent(kode_kembali),
      '_blank', 'width=800,height=600');
  }

  /* printAll - nama fungsi DIJAGA. */
  function printAll() {
    window.open('print_all_pengembalian.php', '_blank', 'width=800,height=600');
  }
</script>

<?php include '../Layouts/footer.php'; ?>
