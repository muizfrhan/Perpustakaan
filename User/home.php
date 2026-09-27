<?php
/**
 * Katalog Buku Publik
 *
 * YANG DIPERTAHANKAN (kontak backend tidak berubah):
 *  - Query "buku populer" (LIMIT 6, urutan jumlah peminjaman)
 *  - Endpoint AJAX  : get_detail_buku.php?kode_buku=...
 *  - Nama fungsi JS : loadDetailForm(kodeBuku)
 *  - Modal id       : #detailModal, #modalContent
 *
 * PERUBAHAN:
 *  - + b.stok, b.kategori, b.pengarang (dibutuhkan untuk badge ketersediaan
 *    dan kartu yang lebih informatif)
 *  - Pencarian instan diserialisasi ke JSON dan difilter di sisi klien,
 *    sehingga TIDAK ada route/query baru.
 *  - Bootstrap -> Tailwind
 */
require_once '../Config/koneksi.php';
include 'header.php';

$query = $conn->query("
SELECT 
    b.kode_buku, 
    b.judul_buku, 
    b.cover, 
    b.penerbit,
    b.pengarang,
    b.kategori,
    b.stok,
    b.status,
    COUNT(p.kode_pinjam) AS jumlah_peminjaman
FROM 
    buku b
LEFT JOIN 
    peminjaman p ON b.kode_buku = p.kode_buku
GROUP BY 
    b.kode_buku
ORDER BY 
    jumlah_peminjaman DESC
LIMIT 6;
");
$buku = $query->fetchAll(PDO::FETCH_ASSOC);

// Ringkasan untuk hero section.
$totalBuku   = (int) $conn->query('SELECT COUNT(*) FROM buku')->fetchColumn();
$totalTersedia = (int) $conn->query("SELECT COALESCE(SUM(stok), 0) FROM buku WHERE status <> 'Kosong'")->fetchColumn();

/**
 * Badge status mengikuti nilai kolom `status` di tabel buku:
 * 'Tersedia' | 'Dipinjam' | 'Kosong'
 */
function badgeStok(array $b): string
{
    $stok = (int) $b['stok'];

    if ($stok <= 0) {
        return '<span class="badge-empty">'
             . '<svg class="h-3 w-3" fill="currentColor" viewBox="0 0 12 12"><path d="M6 0a6 6 0 100 12A6 6 0 006 0z" /></svg>'
             . 'Tidak Tersedia</span>';
    }

    if ($stok === 1) {
        return '<span class="badge-borrowed">'
             . '<svg class="h-3 w-3" fill="currentColor" viewBox="0 0 12 12"><path d="M6 0a6 6 0 100 12A6 6 0 006 0z" /></svg>'
             . 'Sisa 1 eksemplar</span>';
    }

    return '<span class="badge-available">'
         . '<svg class="h-3 w-3" fill="currentColor" viewBox="0 0 12 12"><path d="M6 0a6 6 0 100 12A6 6 0 006 0z" /></svg>'
         . 'Tersedia &middot; ' . $stok . ' eksemplar</span>';
}
?>

<!-- ================= HERO ================= -->
<section class="relative overflow-hidden rounded-3xl bg-slate-900 px-6 py-10 sm:px-10 sm:py-14">
  <!-- dekorasi gradient, pointer-events-none agar tidak menghalangi klik -->
  <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand-500/25 blur-3xl" aria-hidden="true"></div>
  <div class="pointer-events-none absolute -bottom-32 -left-16 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>

  <div class="relative max-w-2xl">
    <span class="badge-info mb-4 bg-white/10 text-brand-100 ring-white/20">
      <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
      Perpustakaan Digital Universitas
    </span>

    <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
      Temukan buku yang<br class="hidden sm:block" /> Anda cari, dalam hitungan detik.
    </h1>

    <p class="mt-4 max-w-xl text-sm leading-relaxed text-slate-300 sm:text-base">
      Jelajahi koleksi kami, pantau ketersediaan buku secara real-time,
      dan kelola riwayat peminjaman Anda dalam satu tempat.
    </p>

    <!-- Ringkasan angka -->
    <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
      <div>
        <dt class="text-xs uppercase tracking-wider text-slate-400">Total Koleksi</dt>
        <dd class="mt-1 text-2xl font-bold text-white"><?= number_format($totalBuku, 0, ',', '.') ?></dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wider text-slate-400">Eksemplar Tersedia</dt>
        <dd class="mt-1 text-2xl font-bold text-emerald-400"><?= number_format($totalTersedia, 0, ',', '.') ?></dd>
      </div>
      <div>
        <dt class="text-xs uppercase tracking-wider text-slate-400">Sedang Dipinjam</dt>
        <dd class="mt-1 text-2xl font-bold text-amber-400"><?= count($buku) ?></dd>
      </div>
    </dl>
  </div>
</section>

<!-- ================= PENCARIAN INSTAN ================= -->
<section class="sticky top-16 z-30 -mx-4 mt-6 bg-slate-50/90 px-4 py-3 backdrop-blur-lg sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
  <div class="mx-auto max-w-2xl">
    <label for="cariBuku" class="sr-only">Cari buku</label>
    <div class="relative">
      <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
           fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
      </svg>

      <input type="search" id="cariBuku" autocomplete="off" placeholder="Cari judul, pengarang, atau penerbit..."
             class="field py-3.5 pl-11 pr-10 text-base shadow-card" />

      <button type="button" id="btnResetCari"
              class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
              aria-label="Bersihkan pencarian">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <p id="infoCari" class="mt-2 px-1 text-xs text-slate-500" role="status" aria-live="polite"></p>
  </div>
</section>

<!-- ================= GRID KATALOG ================= -->
<section class="mt-4">
  <div class="mb-4 flex items-end justify-between gap-4">
    <div>
      <h2 class="text-section" id="judulSection">Buku Terpopuler</h2>
      <p class="text-muted mt-0.5">Diurutkan berdasarkan jumlah peminjaman</p>
    </div>
    <a href="dashboard.php" class="btn-secondary btn-sm hidden shrink-0 sm:inline-flex">
      Riwayat saya
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
      </svg>
    </a>
  </div>

  <?php if (!$buku): ?>
    <!-- Empty state -->
    <div class="card-base flex flex-col items-center justify-center px-6 py-16 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </span>
      <h3 class="mt-4 text-base font-semibold text-slate-900">Katalog masih kosong</h3>
      <p class="text-muted mt-1 max-w-sm">Buku yang ditambahkan admin akan muncul di sini secara otomatis.</p>
    </div>
  <?php else: ?>
    <div id="gridBuku" class="grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($buku as $i => $b): ?>
        <article class="card-base card-hover group flex flex-col overflow-hidden"
                 style="animation-delay: <?= $i * 60 ?>ms"
                 data-cari="<?= htmlspecialchars(strtolower(($b['judul_buku'] ?? '') . ' ' . ($b['pengarang'] ?? '') . ' ' . ($b['penerbit'] ?? '') . ' ' . ($b['kategori'] ?? ''))) ?>">

          <!-- Cover -->
          <div class="relative aspect-[3/4] overflow-hidden bg-slate-100">
            <img src="../Assets/uploads/<?= htmlspecialchars($b['cover']) ?>"
                 alt="Cover <?= htmlspecialchars($b['judul_buku']) ?>"
                 loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />

            <div class="absolute left-3 top-3">
              <?= badgeStok($b) ?>
            </div>

            <?php if ((int) $b['jumlah_peminjaman'] > 0): ?>
              <span class="absolute right-3 top-3 rounded-lg bg-slate-900/75 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                <?= (int) $b['jumlah_peminjaman'] ?>x dipinjam
              </span>
            <?php endif; ?>
          </div>

          <!-- Body -->
          <div class="flex flex-1 flex-col p-4">
            <p class="text-label"><?= htmlspecialchars($b['kategori'] ?? '-') ?></p>

            <h3 class="mt-1.5 line-clamp-2 text-sm font-semibold leading-snug text-slate-900">
              <?= htmlspecialchars($b['judul_buku']) ?>
            </h3>

            <p class="mt-1 line-clamp-1 text-xs text-slate-500">
              <?= htmlspecialchars($b['pengarang'] ?? '-') ?>
            </p>

            <div class="mt-auto pt-4">
              <button type="button"
                      class="btn-secondary btn-sm w-full group-hover:border-brand-300 group-hover:text-brand-700"
                      onclick="loadDetailForm('<?= htmlspecialchars($b['kode_buku']) ?>')">
                Lihat Detail
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Hasil pencarian kosong -->
    <div id="bukuTidakDitemukan" class="card-base hidden flex-col items-center justify-center px-6 py-16 text-center">
      <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
      </span>
      <h3 class="mt-4 text-base font-semibold text-slate-900">Buku tidak ditemukan</h3>
      <p class="text-muted mt-1">Coba kata kunci lain, misalnya judul atau nama pengarang.</p>
    </div>
  <?php endif; ?>
</section>

<!-- ================= MODAL DETAIL ================= -->
<!--
  Modal dibangun dengan Tailwind + vanilla JS, bukan Bootstrap Modal.
  Alasannya: header.php tidak lagi memuat Bootstrap CSS, dan mencampur
  dua framework za leading to component yang tidak bisa distyle.

  Yang DIPERTAHANKAN agar tidak merusak pemanggil:
    - id #detailModal  (tetap dibaca sebagai target)
    - id #modalContent (tetap diisi oleh loadDetailForm)
-->
<div id="detailModal"
     class="fixed inset-0 z-50 hidden items-end justify-center p-0 sm:items-center sm:p-6"
     role="dialog" aria-modal="true" aria-labelledby="detailModalLabel">

  <!-- backdrop -->
  <div data-modal-backdrop class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>

  <!-- panel -->
  <div data-modal-panel
       class="relative flex max-h-[92vh] w-full max-w-3xl translate-y-6 flex-col overflow-hidden rounded-t-3xl
              bg-white shadow-lift transition-all duration-300
              sm:translate-y-0 sm:rounded-3xl
              sm:scale-95">

    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
      <h2 class="text-base font-semibold text-slate-900" id="detailModalLabel">Detail Buku</h2>
      <button type="button" data-modal-close
              class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
              aria-label="Tutup dialog">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="overflow-y-auto overscroll-contain">
      <div id="modalContent">
        <div class="flex items-center justify-center gap-3 px-6 py-20 text-slate-400">
          <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          <span class="text-sm">Memuat detail buku...</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  /* ============================================================
     Pencarian instan (client-side).
     Data sudah ada di DOM, jadi tidak ada request ke server:
     terasa instan dan tidak menambah beban database.
     ============================================================ */
  (function () {
    const input    = document.getElementById('cariBuku');
    const grid     = document.getElementById('gridBuku');
    const kosong   = document.getElementById('bukuTidakDitemukan');
    const info     = document.getElementById('infoCari');
    const btnReset = document.getElementById('btnResetCari');
    const judul    = document.getElementById('judulSection');

    if (!input || !grid) return;

    const kartu = Array.from(grid.querySelectorAll('[data-cari]'));
    const total = kartu.length;

    function jalankan() {
      const q      = input.value.trim().toLowerCase();
      let tampil   = 0;

      kartu.forEach((el) => {
        const cocok = q === '' || el.dataset.cari.includes(q);
        el.classList.toggle('hidden', !cocok);
        if (cocok) tampil++;
      });

      if (judul) judul.textContent = q === '' ? 'Buku Terpopuler' : 'Hasil Pencarian';
      if (kosong) kosong.classList.toggle('hidden', tampil !== 0 || q === '');

      btnReset?.classList.toggle('hidden', q === '');
      input.setAttribute('aria-expanded', String(tampil > 0));

      info.textContent = q === ''
        ? (total > 0 ? total + ' judul ditampilkan' : '')
        : tampil + ' dari ' + total + ' judul cocok dengan "' + input.value.trim() + '"';
    }

    let timer;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(jalankan, 120);   // debounce
    });

    btnReset?.addEventListener('click', () => {
      input.value = '';
      jalankan();
      input.focus();
    });

    jalankan();
  })();
</script>

<script>
  /* ============================================================
     Komponen modal: buka/tutup, ESC, klik backdrop, focus trap,
     dan lock scroll body. Tanpa dependensi library.
     ============================================================ */
  (function () {
    const modal  = document.getElementById('detailModal');
    const backdrop = modal?.querySelector('[data-modal-backdrop]');
    const panel  = modal?.querySelector('[data-modal-panel]');
    if (!modal || !backdrop || !panel) return;

    let sebelumnyaFokus = null;

    function buka() {
      sebelumnyaFokus = document.activeElement;

      modal.classList.remove('hidden');
      modal.classList.add('flex');
      document.body.style.overflow = 'hidden';       // lock scroll

      requestAnimationFrame(() => {
        backdrop.classList.replace('opacity-0', 'opacity-100');
        panel.classList.remove('translate-y-6', 'sm:scale-95');
      });

      panel.querySelector('[data-modal-close]')?.focus();
    }

    function tutup() {
      backdrop.classList.replace('opacity-100', 'opacity-0');
      panel.classList.add('translate-y-6', 'sm:scale-95');
      document.body.style.overflow = '';

      setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }, 250);

      sebelumnyaFokus?.focus();
    }

    // Dipakai oleh loadDetailForm().
    window.bukaDetailModal = buka;

    modal.querySelectorAll('[data-modal-close]').forEach((b) => b.addEventListener('click', tutup));
    backdrop.addEventListener('click', tutup);

    document.addEventListener('keydown', (e) => {
      if (modal.classList.contains('hidden')) return;

      if (e.key === 'Escape') { tutup(); return; }

      // Focus trap: Tab tidak boleh keluar dari dialog.
      if (e.key === 'Tab') {
        const fokusable = panel.querySelectorAll('a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])');
        if (!fokusable.length) return;

        const pertama = fokusable[0];
        const terakhir = fokusable[fokusable.length - 1];

        if (e.shiftKey && document.activeElement === pertama) {
          e.preventDefault(); terakhir.focus();
        } else if (!e.shiftKey && document.activeElement === terakhir) {
          e.preventDefault(); pertama.focus();
        }
      }
    });
  })();

  /* ============================================================
     loadDetailForm - nama fungsi & endpoint DIJAGA karena
     dipanggil dari onclick pada kartu buku.
     ============================================================ */
  function loadDetailForm(kodeBuku) {
    window.bukaDetailModal?.();

    const box = document.getElementById('modalContent');
    if (!box) return;

    box.innerHTML = `
      <div class="flex items-center justify-center gap-3 px-6 py-20 text-slate-400">
        <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <span class="text-sm">Memuat detail buku...</span>
      </div>`;

    fetch(`get_detail_buku.php?kode_buku=${encodeURIComponent(kodeBuku)}`)
      .then((r) => r.json())
      .then((d) => {
        // Semua nilai dari server di-escape sebelum masuk innerHTML.
        const esc = (v) => String(v ?? '-').replace(/[&<>"']/g, (c) => ({
          '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        const stok = Number(d.stok);
        const badge = stok <= 0
          ? '<span class="badge-empty">Tidak Tersedia</span>'
          : (stok === 1
              ? '<span class="badge-borrowed">Sisa 1 eksemplar</span>'
              : `<span class="badge-available">Tersedia &middot; ${stok} eksemplar</span>`);

        const baris = (label, nilai) => `
          <div class="flex items-baseline justify-between gap-4 border-b border-slate-100 py-2.5 last:border-0">
            <dt class="text-xs font-medium uppercase tracking-wider text-slate-400">${label}</dt>
            <dd class="text-sm font-medium text-slate-800 text-right">${nilai}</dd>
          </div>`;

        box.innerHTML = `
          <div class="grid gap-0 sm:grid-cols-[240px_1fr]">
            <div class="bg-slate-100 p-5">
              <img src="../Assets/uploads/${esc(d.cover)}"
                   alt="Cover ${esc(d.judul_buku)}"
                   class="aspect-[3/4] w-full rounded-xl object-cover shadow-card" />
            </div>

            <div class="p-5 sm:p-6">
              <div class="flex flex-wrap items-center gap-2">${badge}</div>

              <h3 class="mt-3 text-xl font-bold leading-snug tracking-tight text-slate-900">
                ${esc(d.judul_buku)}
              </h3>
              <p class="mt-1 text-sm text-slate-500">${esc(d.pengarang)}</p>

              <dl class="mt-5">
                ${baris('Penerbit', esc(d.penerbit))}
                ${baris('Tanggal Terbit', esc(d.tanggal_terbit))}
                ${baris('Halaman', esc(d.jumlah_halaman) + ' hlm')}
                ${baris('Bahasa', esc(d.bahasa))}
                ${baris('Kategori', esc(d.kategori ?? '-'))}
              </dl>

              <div class="mt-5 rounded-xl bg-slate-50 p-4">
                <p class="text-label mb-1.5">Deskripsi</p>
                <p class="text-sm leading-relaxed text-slate-600">${esc(d.deskripsi_buku)}</p>
              </div>
            </div>
          </div>`;
      })
      .catch(() => {
        box.innerHTML = `
          <div class="px-6 py-16 text-center">
            <p class="text-sm font-medium text-slate-900">Gagal memuat detail buku</p>
            <p class="text-muted mt-1">Periksa koneksi Anda lalu coba lagi.</p>
          </div>`;
      });
  }
</script>

<?php include 'footer.php'; ?>
