<?php
/**
 * Data Pengembalian.
 *
 * QUERY, URL FILTER (?filter= & ?page=), ID MODAL (#tambahPengembalianModal,
 * #editPengembalianModal), ID CONTAINER (#tambahModalContent,
 * #editModalContent), dan nama fungsi TIDAK BERUBAH.
 * Yang diubah hanya markup + CSS.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'pengembalian';
include '../Layouts/header.php';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1); // Halaman saat ini
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

$e = static fn($v) => htmlspecialchars((string) $v);

/** Peta kondisi buku -> kelas badge. */
$badgeKondisi = [
    'Bagus'  => 'badge-safe',
    'Rusak'  => 'badge-due',
    'Hilang' => 'badge-late',
];

$filterMenu = [
    'semua'       => 'Semua',
    'belum_lunas' => 'Belum Lunas',
    'lunas'       => 'Lunas',
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
      <?= number_format($totalRows, 0, ',', '.') ?> transaksi
      &middot; filter: <?= $e($filterMenu[$filter] ?? 'Semua') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter Status -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="pengembalianFilterDropdown" class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        <?= $e($filterMenu[$filter] ?? 'Filter') ?>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="pengembalianFilterDropdown" data-dropdown class="dropdown right-0">
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
            <?= $e($label) ?>
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
    <button type="button" onclick="printAll()" class="btn-secondary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
      </svg>
      Cetak Semua
    </button>

    <!-- Tambah Pengembalian -->
    <button type="button" data-modal-open="tambahPengembalianModal" class="btn-primary btn-sm">
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
            $kodeKembali = $e($row['kode_kembali']);
            $kondisi     = $row['kondisi_buku'];
            $lunas       = $row['status'] == 'Lunas';

            $waLink = 'https://wa.me/' . $row['no_telp']
                . '?text=Halo%20' . urlencode($row['nama_anggota'])
                . ',%20kami%20ingin%20mengingatkan%20bahwa%20Anda%20memiliki%20denda%20pengembalian%20buku%20sebesar%20Rp'
                . number_format($row['denda'], 0, ',', '.')
                . '.%0A%0A%20Silakan%20segera%20melunasi.%20Transfer%20Pelunasan%20bisa%20melalui%20salah%20satu%20No%20Rekening%20Kami%20berikut:%0A%0A%20Dana%20:%20085777219250%0A%20Gopay%20:%20085777219250%0A%20Bank%20Jago%20:%20109060269590%0A%0A%20Jika%20sudah%20transfer%20mohon%20segera%20konfirmasi.%20Terima%20Kasih.';
            ?>
          <tr>
            <td class="text-center">
              <code class="code-chip"><?= $kodeKembali ?></code>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= $e($row['nama_anggota']) ?></td>
            <td class="whitespace-nowrap"><?= $e($row['judul_buku']) ?></td>
            <td class="whitespace-nowrap"><?= date('d-m-Y', strtotime($row['tgl_kembali'])) ?></td>
            <td>
              <?php if ($kondisi == 'Bagus') { ?>
                <span class="badge-safe">Bagus</span>
              <?php } elseif ($kondisi == 'Rusak') { ?>
                <span class="badge-due">Rusak</span>
              <?php } else { ?>
                <span class="badge-late">Hilang</span>
              <?php } ?>
            </td>

            <td class="whitespace-nowrap">Rp<?= number_format($row['denda'], 2, ',', '.') ?></td>
            <td>
              <?php if ($row['status'] == 'Lunas') { ?>
                <span class="badge-safe">Lunas</span>
              <?php } else { ?>
                <span class="badge-late">Belum Lunas</span>
              <?php } ?>
            </td>

            <td>
              <?php if ($row['pembayaran'] == 'Tidak Ada') { ?>
                <span class="badge-neutral">Tidak Ada</span>
              <?php } elseif ($row['pembayaran'] == 'Kes') { ?>
                <span class="badge-due">Cash</span>
              <?php } else { ?>
                <span class="badge-info">Transfer</span>
              <?php } ?>
            </td>
            <td class="text-center">
              <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                <?php if (!$lunas): ?>
                  <button type="button" data-modal-open="editPengembalianModal"
                          onclick="loadEditForm('<?= $kodeKembali ?>')" class="btn-warning btn-sm">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Edit
                  </button>
                <?php endif; ?>

                <button type="button" onclick="printData('<?= $kodeKembali ?>')" class="btn-secondary btn-sm">
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                  </svg>
                  Print
                </button>

                <?php if (!$lunas): ?>
                  <a href="<?= $e($waLink) ?>" target="_blank" rel="noopener"
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
<div id="tambahPengembalianModal" data-modal role="dialog" aria-modal="true" aria-labelledby="tambahPengembalianLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-2xl">
    <div class="modal-header">
      <h2 class="modal-title" id="tambahPengembalianLabel">Tambah Data Pengembalian</h2>
      <button type="button" data-modal-close class="modal-close" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="modal-body" id="tambahModalContent">
      <!-- Form akan dimuat di sini menggunakan AJAX -->
    </div>
  </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div id="editPengembalianModal" data-modal role="dialog" aria-modal="true" aria-labelledby="editPengembalianLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-lg">
    <div class="modal-header">
      <h2 class="modal-title" id="editPengembalianLabel">Edit Data Pengembalian</h2>
      <button type="button" data-modal-close class="modal-close" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="modal-body" id="editModalContent">
      <!-- Form akan dimuat di sini menggunakan AJAX -->
    </div>
  </div>
</div>

<script>
  /* AJAX untuk memuat form tambah pengembalian (pengganti event show.bs.modal). */
  document.getElementById('tambahPengembalianModal').addEventListener('modal:open', function () {
    Pusaku.loadInto('#tambahModalContent', 'add_pengembalian.php', 'Gagal memuat form pengembalian.')
      .then(() => {
        setupAutocomplete(); // Panggil setupAutocomplete setelah konten dimuat
        // Fragment AJAX tidak mengeksekusi <script>, jadi init dipanggil manual.
        if (typeof window.initFormKembali === 'function') window.initFormKembali();
      });
  });

  /* Fungsi untuk menangani autocomplete - nama fungsi DIJAGA. */
  function setupAutocomplete() {
    const inputKodePinjam = document.getElementById('kode_pinjam');
    const searchResults = document.getElementById('search_results');

    if (!inputKodePinjam || !searchResults) return;

    const tutup = () => {
      searchResults.innerHTML = '';
      searchResults.classList.add('hidden');
    };

    inputKodePinjam.addEventListener('input', function () {
      const query = inputKodePinjam.value;

      if (query.length > 0) {
        fetch(`search_kode_pinjam.php?kode_pinjam=${query}`)
          .then(response => response.json())
          .then(data => {
            let html = '';
            if (data.length > 0) {
              data.forEach(item => {
                html += `<div class="autocomplete-item" data-kode="${item.kode_pinjam}" data-nama="${item.nama_anggota}">
                                    <strong>${item.kode_pinjam}</strong><span class="text-slate-400">-</span>${item.nama_anggota}
                                </div>`;
              });
            } else {
              html = '<div class="autocomplete-empty">Tidak ada hasil yang ditemukan</div>';
            }
            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');

            searchResults.querySelectorAll('[data-kode]').forEach(row => {
              row.addEventListener('mousedown', (ev) => {
                ev.preventDefault();
                selectKodePinjam(row.dataset.kode, row.dataset.nama);
              });
            });
          });
      } else {
        tutup();
      }
    });

    // Tutup saat klik di luar atau menekan Escape.
    document.addEventListener('click', (ev) => {
      if (!searchResults.contains(ev.target) && ev.target !== inputKodePinjam) tutup();
    });
    inputKodePinjam.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape') tutup();
    });
  }

  /* Fungsi untuk memilih item dari hasil autocomplete - nama fungsi DIJAGA. */
  window.selectKodePinjam = function (kodePinjam, namaAnggota) {
    const inputKodePinjam = document.getElementById('kode_pinjam');
    const searchResults = document.getElementById('search_results');
    if (inputKodePinjam) inputKodePinjam.value = kodePinjam;
    if (searchResults) {
      searchResults.innerHTML = '';
      searchResults.classList.add('hidden');
    }
  };

  /* AJAX untuk memuat form edit - nama fungsi DIJAGA. */
  function loadEditForm(kode_kembali) {
    Pusaku.loadInto('#editModalContent', 'edit_pengembalian.php?kode_kembali=' + encodeURIComponent(kode_kembali),
      'Gagal memuat data pengembalian.');
  }

  /* Fitur Searching - dipertahankan sebagai alias agar tidak merusak pemanggil lama. */
  function searchTable() {
    const input = document.getElementById('search');
    if (!input) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /* Fitur Print - nama fungsi DIJAGA. */
  function printData(kode_kembali) {
    // Redirect ke halaman cetak atau buka pop-up untuk mencetak
    window.open(`print_pengembalian.php?kode_kembali=${kode_kembali}`, '_blank', 'width=800,height=600');
  }

  function printAll() {
    // Redirect ke halaman cetak seluruh data
    window.open('print_all_pengembalian.php', '_blank', 'width=800,height=600');
  }

  /* Panggil setupAutocomplete saat halaman dimuat (aman jika form belum ada). */
  document.addEventListener('DOMContentLoaded', setupAutocomplete);
</script>

<?php include '../Layouts/footer.php'; ?>
