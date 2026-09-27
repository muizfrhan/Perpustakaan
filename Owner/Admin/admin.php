<?php
/**
 * Data Petugas / Admin (Panel Owner).
 *
 * QUERY, URL FILTER (?status_filter= & ?page=), ID MODAL
 * (#tambahPetugasModal, #editPetugasModal), dan nama fungsi
 * TIDAK BERUBAH. Yang diubah hanya markup + CSS.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'admin';
include '../Layouts/header.php';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1); // Halaman saat ini
$offset = ($page - 1) * $limit;

// Filter status petugas
$statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'semua'; // Default 'semua'
$whereClause = '';
if ($statusFilter === 'aktif') {
  $whereClause = "WHERE p.status = 'Aktif'";
} elseif ($statusFilter === 'tidak_aktif') {
  $whereClause = "WHERE p.status = 'Tidak Aktif'";
}

// Hitung total data dengan filter
$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM petugas p $whereClause");
$totalQuery->execute();
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = (int) $totalResult['total'];
$totalPages = (int) ceil($totalRows / $limit);

// Jaga agar halaman tidak melewati jumlah halaman yang ada.
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $limit;

// Ambil data sesuai filter dan halaman.
// jml_pinjaman dipakai untuk memberi tahu user berapa banyak riwayat yang
// akan ikut terhapus kalau ia memilih "Hapus" (lihat ON DELETE CASCADE di
// hapus_petugas.php).
$result = $conn->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM peminjaman pm WHERE pm.id_petugas = p.id_petugas) AS jml_pinjaman
       FROM petugas p
       $whereClause
      LIMIT :limit OFFSET :offset"
);
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$filterMenu = [
    'semua'       => 'Semua Petugas',
    'aktif'       => 'Aktif',
    'tidak_aktif' => 'Tidak Aktif',
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
    <h1 class="text-title">Data Petugas</h1>
    <p class="text-muted mt-1">
      <?= number_format($totalRows, 0, ',', '.') ?> petugas terdaftar
      &middot; filter: <?= htmlspecialchars($filterMenu[$statusFilter] ?? 'Semua Petugas') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="statusFilterDropdown" class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        <?= htmlspecialchars($filterMenu[$statusFilter] ?? 'Filter') ?>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="statusFilterDropdown" data-dropdown class="dropdown hidden right-0">
        <?php foreach ($filterMenu as $key => $label): ?>
          <a href="?status_filter=<?= urlencode($key) ?>&page=1"
             class="dropdown-item <?= $statusFilter === $key ? 'dropdown-item-active' : '' ?>">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
              <?php if ($statusFilter === $key): ?>
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
      <input type="search" id="search" data-table-search="#petugasTable" autocomplete="off"
             class="field field-sm pl-10" placeholder="Cari Petugas..." />
    </div>

    <!-- Tambah Petugas -->
    <button type="button" data-modal-open="tambahPetugasModal" class="btn-primary btn-sm">
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
    <table class="table-base" id="petugasTable">
      <thead>
        <tr>
          <th class="text-center">ID</th>
          <th>Profil</th>
          <th>Nama</th>
          <th>Username</th>
          <th>No. Telp</th>
          <th>Jenis Kelamin</th>
          <th class="text-center">Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="8" class="py-14 text-center text-slate-500">
              Belum ada petugas yang cocok dengan filter ini.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)):
            $id     = htmlspecialchars((string) $row['id_petugas']);
            $aktif  = $row['status'] == 'Aktif';
            ?>
          <tr>
            <td class="text-center">
              <code class="code-chip"><?= $id ?></code>
            </td>
            <td>
        <?php
        $fotoBaris = \App\Services\Profil::urlFoto($row['profil_gambar'] ?? null, '../../');
        if ($fotoBaris === null && !empty($row['profil_gambar'])) {
            // Foto berformat lama (nama asli) masih di Assets/uploads/.
            $lama = '../../Assets/uploads/' . basename((string) $row['profil_gambar']);
            if (is_file(__DIR__ . '/../../Assets/uploads/' . basename((string) $row['profil_gambar']))) {
                $fotoBaris = $lama;
            }
        }
        ?>
        <?php if ($fotoBaris): ?>
          <img src="<?= htmlspecialchars($fotoBaris) ?>"
               alt="Profil <?= htmlspecialchars($row['nama_petugas']) ?>"
               class="h-12 w-12 rounded-full object-cover ring-1 ring-slate-200" />
              <?php else: ?>
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-500">
                  <?= htmlspecialchars(strtoupper(mb_substr((string) $row['nama_petugas'], 0, 1))) ?>
                </span>
              <?php endif; ?>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= htmlspecialchars($row['nama_petugas']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['username']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars((string) ($row['no_telp'] ?: '-')) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
            <td class="text-center">
              <?php if ($aktif): ?>
                <span class="badge-safe">Aktif</span>
              <?php else: ?>
                <span class="badge-empty">Tidak Aktif</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                <?php
                $terkunci = owner_terkunci($row['username'] ?? '');
                ?>
                <?php if ($aktif && !$terkunci): ?>
                  <form method="POST" action="delete_petugas.php" class="inline"
                        data-konfirmasi="nonaktifkan"
                        data-nama="<?= htmlspecialchars($row['nama_petugas']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id_petugas" value="<?= $id ?>">
                    <button type="submit"
                            class="btn btn-sm border border-amber-200 bg-white text-amber-700 hover:bg-amber-50">
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                      Nonaktif
                    </button>
                  </form>
                <?php elseif ($terkunci): ?>
                  <span class="badge-safe" title="Akun owner terkunci, tidak bisa dinonaktifkan atau dihapus">
                    <svg class="mr-1 inline h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Terkunci
                  </span>
                <?php endif; ?>

                <?php if ($aktif): ?>
                  <button type="button" data-modal-open="editPetugasModal"
                          onclick="loadEditForm('<?= $id ?>')"
                          class="btn-secondary btn-sm">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                    Edit
                  </button>
                <?php endif; ?>

                <?php if (!$terkunci): ?>
                  <form method="POST" action="hapus_petugas.php" class="inline"
                        data-konfirmasi="hapus"
                        data-nama="<?= htmlspecialchars($row['nama_petugas']) ?>"
                        data-riwayat="<?= (int) ($row['jml_pinjaman'] ?? 0) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id_petugas" value="<?= $id ?>">
                    <button type="submit" class="btn btn-sm border border-rose-200 bg-white text-rose-600 hover:bg-rose-50">
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                      </svg>
                      Hapus
                    </button>
                  </form>
                <?php endif; ?>

                <button type="button" onclick="printPetugas('<?= $id ?>')" class="btn-secondary btn-sm">
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                  </svg>
                  Print
                </button>
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
        <a href="?page=<?= $page - 1; ?>&status_filter=<?= urlencode($statusFilter) ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <?php if (count($rentangHalaman) > 1): ?>
          <div class="hidden items-center gap-1 sm:flex">
            <?php foreach ($rentangHalaman as $i): ?>
              <a href="?page=<?= $i; ?>&status_filter=<?= urlencode($statusFilter) ?>"
                 class="page-link <?= $i === $page ? 'page-link-active' : '' ?>"
                 <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="?page=<?= $page + 1; ?>&status_filter=<?= urlencode($statusFilter) ?>"
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
<div id="tambahPetugasModal" data-modal role="dialog" aria-modal="true" aria-labelledby="tambahPetugasLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-2xl">
    <div class="modal-header">
      <h2 class="modal-title" id="tambahPetugasLabel">Tambah Petugas Baru</h2>
      <button type="button" data-modal-close class="modal-close" aria-label="Tutup">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="modal-body" id="modalContent">
      <!-- Form akan dimuat di sini menggunakan AJAX -->
    </div>
  </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div id="editPetugasModal" data-modal role="dialog" aria-modal="true" aria-labelledby="editPetugasLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-2xl">
    <div class="modal-header">
      <h2 class="modal-title" id="editPetugasLabel">Edit Data Petugas</h2>
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
  /* printPetugas - nama fungsi DIJAGA. */
  function printPetugas(id_petugas) {
    const printWindow = window.open('print_petugas.php?id_petugas=' + encodeURIComponent(id_petugas), '_blank');
    if (printWindow) printWindow.focus();
  }

  /*
   * Konfirmasi sebelum form hapus/nonaktifkan dikirim.
   *
   * Dulu ini ditangani per-tombol lewat onclick yang memanggil
   * window.location.href ke endpoint GET. Sekarang form-nya POST beneran
   * (dengan token CSRF), jadi konfirmasinya dipasang satu delegation
   * listener di bawah - termasuk untuk form yang disuntik lewat AJAX.
   */
  (function () {
    document.addEventListener('submit', function (ev) {
      const form = ev.target;
      if (!(form instanceof HTMLFormElement) || !form.dataset.konfirmasi) return;

      const nama  = form.dataset.nama || 'akun ini';
      const jenis = form.dataset.konfirmasi;

      if (jenis === 'nonaktifkan') {
        ev.preventDefault();
        Pusaku.confirm({
          judul: 'Nonaktifkan Petugas',
          pesan: 'Nonaktifkan akun "' + nama + '"? Akun tidak bisa login lagi, '
               + 'tetapi seluruh riwayat peminjamannya tetap tersimpan.',
          labelYa: 'Ya, nonaktifkan'
        }).then(function (ya) {
          if (ya) form.submit();
        });
        return;
      }

      if (jenis === 'hapus') {
        ev.preventDefault();

        const riwayat = parseInt(form.dataset.riwayat || '0', 10);
        // Pesan dibuat eksplisit soal akibat cascade, supaya user tidak
        // menghapus riwayat tanpa sadar.
        const akibat = riwayat > 0
          ? 'Riwayat ' + riwayat + ' peminjaman milik "' + nama + '" '
            + 'JUGA AKAN HILANG PERMANEN dan tidak bisa dikembalikan.'
          : 'Akun "' + nama + '" akan dihapus permanen.';

        Pusaku.confirm({
          judul: 'Hapus Akun Permanen?',
          pesan: akibat + ' Tindakan ini tidak bisa dibatalkan. '
               + 'Jika hanya ingin mencegah login, gunakan tombol Nonaktif.',
          labelYa: 'Ya, hapus permanen'
        }).then(function (ya) {
          if (ya) form.submit();
        });
      }
    });
  })();

  /* loadEditForm - nama fungsi & endpoint DIJAGA. */
  function loadEditForm(id_petugas) {
    Pusaku.loadInto('#editModalContent', 'edit_petugas.php?id_petugas=' + encodeURIComponent(id_petugas),
      'Gagal memuat form edit petugas.').then(bindNoTelpSanitizer);
  }

  /* Pencarian tabel (data-table-search) sudah menangani penyaringan; */
  /* searchTable dipertahankan sebagai alias agar tidak merusak pemanggil lama. */
  function searchTable() {
    const input = document.getElementById('search');
    if (!input) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  /* Fragment AJAX tidak mengeksekusi <script> di dalamnya, jadi penomoran */
  /* telepon tetap diikat dari sini setelah form dimuat. */
  function bindNoTelpSanitizer() {
    const input = document.getElementById('no_telp');
    if (!input || input.dataset.bound === '1') return;
    input.dataset.bound = '1';
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  }

  /* Muat form tambah saat modal dibuka (pengganti event show.bs.modal). */
  document.getElementById('tambahPetugasModal').addEventListener('modal:open', function () {
    Pusaku.loadInto('#modalContent', 'add_petugas.php', 'Gagal memuat form tambah petugas.')
      .then(bindNoTelpSanitizer);
  });
</script>

<?php include '../Layouts/footer.php'; ?>
