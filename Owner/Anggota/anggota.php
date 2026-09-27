<?php
/**
 * Data Anggota (Panel Owner - hanya baca).
 *
 * QUERY, URL FILTER (?member_filter= & ?page=), dan nama fungsi
 * TIDAK BERUBAH. Yang diubah hanya markup + CSS.
 *
 * Owner bersifat read-only: tidak ada affordance tambah/edit/hapus.
 */
require_once '../../Config/koneksi.php';

$menuAktif = 'anggota';
include '../Layouts/header.php';

// Get the filter for member status
$memberFilter = isset($_GET['member_filter']) ? $_GET['member_filter'] : 'semua'; // Default is 'semua' (all)
$whereClause = '';
if ($memberFilter === 'aktif') {
  $whereClause = "WHERE a.status_mhs = 'Aktif'";
} elseif ($memberFilter === 'tidak_aktif') {
  $whereClause = "WHERE a.status_mhs = 'Tidak Aktif'";
}

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1); // Halaman saat ini
$offset = ($page - 1) * $limit;

// Hitung total data
$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM anggota a $whereClause");
$totalQuery->execute();
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = (int) $totalResult['total'];
$totalPages = (int) ceil($totalRows / $limit);

// Jaga agar halaman tidak melewati jumlah halaman yang ada.
$page = min($page, max(1, $totalPages));
$offset = ($page - 1) * $limit;

// Ambil data sesuai halaman dan filter status anggota dan urutan abjad.
// jml_pinjaman dipakai untuk memberi tahu user berapa riwayat yang akan
// ikut terhapus kalau ia memilih "Hapus" (lihat ON DELETE CASCADE di
// hapus_anggota.php).
$result = $conn->prepare("
    SELECT a.*,
           (SELECT COUNT(*) FROM peminjaman p WHERE p.nim = a.nim) AS jml_pinjaman
    FROM anggota a
    $whereClause
    ORDER BY nama ASC
    LIMIT :limit OFFSET :offset
");

$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

$filterMenu = [
    'semua'       => 'Semua Anggota',
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
    <h1 class="text-title">Data Anggota</h1>
    <p class="text-muted mt-1">
      <?= number_format($totalRows, 0, ',', '.') ?> anggota terdaftar
      &middot; filter: <?= htmlspecialchars($filterMenu[$memberFilter] ?? 'Semua Anggota') ?>
    </p>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <!-- Filter -->
    <div class="relative" data-dropdown>
      <button type="button" data-dropdown-toggle="memberFilterDropdown" class="btn-secondary btn-sm gap-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
        </svg>
        <?= htmlspecialchars($filterMenu[$memberFilter] ?? 'Filter') ?>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </button>

      <div id="memberFilterDropdown" data-dropdown class="dropdown hidden right-0">
        <?php foreach ($filterMenu as $key => $label): ?>
          <a href="?member_filter=<?= urlencode($key) ?>&page=1"
             class="dropdown-item <?= $memberFilter === $key ? 'dropdown-item-active' : '' ?>">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
              <?php if ($memberFilter === $key): ?>
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
      <input type="search" id="search" data-table-search="#anggotaTable" autocomplete="off"
             class="field field-sm pl-10" placeholder="Cari Anggota..." />
    </div>
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
          <th class="text-center">Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$totalRows): ?>
          <tr>
            <td colspan="9" class="py-14 text-center text-slate-500">
              Belum ada anggota yang cocok dengan filter ini.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
          <tr>
            <td class="text-center">
              <code class="code-chip"><?= htmlspecialchars((string) $row['nim']) ?></code>
            </td>
            <td class="whitespace-nowrap font-semibold text-slate-900"><?= htmlspecialchars($row['nama']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars((string) ($row['no_telp'] ?: '-')) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['jurusan']) ?></td>
            <td class="whitespace-nowrap"><?= htmlspecialchars($row['kelas']) ?></td>
            <td class="whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tgl_lahir'])) ?></td>
            <td class="text-center">
              <?php if ($row['status_mhs'] == 'Aktif'): ?>
                <span class="badge-safe">Aktif</span>
              <?php else: ?>
                <span class="badge-empty">Tidak Aktif</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                <?php if ($row['status_mhs'] === 'Aktif'): ?>
                  <form method="POST" action="nonaktifkan_anggota.php" class="inline"
                        data-konfirmasi-anggota="nonaktifkan"
                        data-nama="<?= htmlspecialchars($row['nama']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="nim" value="<?= htmlspecialchars((string) $row['nim']) ?>">
                    <button type="submit"
                            class="btn btn-sm border border-amber-200 bg-white text-amber-700 hover:bg-amber-50">
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                      Nonaktif
                    </button>
                  </form>
                <?php endif; ?>

                <form method="POST" action="hapus_anggota.php" class="inline"
                      data-konfirmasi-anggota="hapus"
                      data-nama="<?= htmlspecialchars($row['nama']) ?>"
                      data-riwayat="<?= (int) ($row['jml_pinjaman'] ?? 0) ?>">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="nim" value="<?= htmlspecialchars((string) $row['nim']) ?>">
                  <button type="submit" class="btn btn-sm border border-rose-200 bg-white text-rose-600 hover:bg-rose-50">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    Hapus
                  </button>
                </form>
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
        <a href="?page=<?= $page - 1; ?>&member_filter=<?= urlencode($memberFilter) ?>"
           class="btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
          Sebelumnya
        </a>

        <?php if (count($rentangHalaman) > 1): ?>
          <div class="hidden items-center gap-1 sm:flex">
            <?php foreach ($rentangHalaman as $i): ?>
              <a href="?page=<?= $i; ?>&member_filter=<?= urlencode($memberFilter) ?>"
                 class="page-link <?= $i === $page ? 'page-link-active' : '' ?>"
                 <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="?page=<?= $page + 1; ?>&member_filter=<?= urlencode($memberFilter) ?>"
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

  /*
   * Konfirmasi sebelum form hapus/nonaktifkan anggota dikirim.
   * Delegation listener, jadi berlaku untuk semua baris sekaligus.
   */
  (function () {
    document.addEventListener('submit', function (ev) {
      const form = ev.target;
      if (!(form instanceof HTMLFormElement) || !form.dataset.konfirmasiAnggota) return;

      ev.preventDefault();

      const nama  = form.dataset.nama || 'anggota ini';
      const jenis = form.dataset.konfirmasiAnggota;

      if (jenis === 'nonaktifkan') {
        Pusaku.confirm({
          judul: 'Nonaktifkan Anggota',
          pesan: 'Nonaktifkan akun "' + nama + '"? Akun tidak bisa login lagi, '
               + 'tetapi riwayat peminjamannya tetap tersimpan.',
          labelYa: 'Ya, nonaktifkan'
        }).then(function (ya) {
          if (ya) form.submit();
        });
        return;
      }

      const riwayat = parseInt(form.dataset.riwayat || '0', 10);
      // ON DELETE CASCADE: menghapus anggota ikut menghapus riwayat pinjamnya.
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
    });
  })();
</script>

<?php include '../Layouts/footer.php'; ?>
