<?php
/**
 * Shell bersama untuk Admin & Owner.
 *
 * Dipakai oleh:
 *   Admin/Layouts/header.php  -> $peran = 'admin'
 *   Owner/Layouts/header.php  -> $peran = 'owner'
 *
 * Kontrak variabel yang harus diisi sebelum include:
 *   $peran      : 'admin' | 'owner'
 *   $namaUser   : string nama untuk topbar
 *   $subUser    : string keterangan peran
 *   $fotoUser   : string nama file gambar (boleh kosong)
 *   $urlPrefix  : '../../'  (relatif dari Layouts ke root project)
 *   $menuAktif  : id menu yang sedang aktif
 *   $urlLogout  : '../logout.php' - relatif dari folder halaman ke root
 *
 * PENTING soal $urlLogout:
 * Halaman Admin/Owner berada DUA level di bawah root project
 * (mis. Admin/Buku/buku.php), sehingga '../logout.php' hanya akan
 * mengarah ke /Admin/logout.php - tidak ada. Yang benar '../../logout.php'
 * supaya menunjuk ke logout.php di root.
 * Sebelumnya shell menulis href="logout.php" yang relatif ke halaman yang
 * sedang dibuka; itu membuat Owner/Pengembalian/logout.php (dan halaman
 * lain) membalas 404.
 * Catatan: panel User berada satu level (/User/...), jadi header-nya
 * memakai '../logout.php' - beda dengan shell ini.
 */

// Memuat kernel: autoloader (App\Services\...), session hardening, dan
// helper (e/audit/flash). Halaman-halaman lama hanya require koneksi.php
// sehingga autoloader tidak terdaftar - tanpa baris ini class
// App\Services\Profil di bawah tidak akan ditemukan.
require_once __DIR__ . '/../bootstrap.php';

$peran     = $peran     ?? 'admin';
$namaUser  = $namaUser  ?? 'Pengguna';
$subUser   = $subUser   ?? ucfirst($peran);
$fotoUser  = $fotoUser  ?? '';
$urlPrefix = $urlPrefix ?? '../../';
$menuAktif = $menuAktif ?? '';
$urlLogout = $urlLogout ?? '../../logout.php';

// ---------------------------------------------------------------------------
// Guard login untuk SELURUH halaman panel Admin & Owner.
//
// Shell ini di-include oleh hampir semua halaman di folder Admin/ dan Owner/.
// Menaruh guard di sini menutup semua halaman sekaligus - sebelumnya
// require_owner()/require_admin() hanya dipanggil di 2 halaman, sehingga
// sisanya (termasuk Owner/Admin/admin.php) bisa dibuka tanpa login.
//
// CATATAN: fragment & endpoint yang tidak memakai shell (mis. add_buku.php,
// delete_petugas.php, search_anggota.php) tetap harus memanggil guard-nya
// sendiri di awal file.
// ---------------------------------------------------------------------------
if ($peran === 'owner') {
    require_owner();
} else {
    require_admin();
}

/**
 * Definisi menu per peran.
 * id_menu harus sama dengan nilai $menuAktif di halaman masing-masing.
 */
$menu = $peran === 'owner' ? [
    ['id' => 'dashboard', 'label' => 'Dashboard',        'href' => '../Dashboard/dashboard.php',  'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
    ['id' => 'admin',     'label' => 'Kelola Admin',    'href' => '../Admin/admin.php',         'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
    ['id' => 'anggota',   'label' => 'Anggota',          'href' => '../Anggota/anggota.php',     'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
    ['id' => 'buku',      'label' => 'Buku',             'href' => '../Buku/buku.php',           'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
    ['id' => 'peminjaman',   'label' => 'Peminjaman',    'href' => '../Peminjaman/peminjaman.php',           'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 006 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
    ['id' => 'pengembalian', 'label' => 'Pengembalian',  'href' => '../Pengembalian/pengembalian.php',     'icon' => 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3m-9-6h.01'],
    ['id' => 'pengaturan',    'label' => 'Pengaturan',    'href' => '../Akun/pengaturan.php',               'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
] : [
    ['id' => 'dashboard',    'label' => 'Dashboard',     'href' => '../Dashboard/dashboard.php',           'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
    ['id' => 'anggota',      'label' => 'Anggota',       'href' => '../Anggota/anggota.php',               'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
    ['id' => 'buku',         'label' => 'Buku',          'href' => '../Buku/buku.php',                     'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
    ['id' => 'peminjaman',   'label' => 'Peminjaman',    'href' => '../Peminjaman/peminjaman.php',           'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 006 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
    ['id' => 'pengembalian', 'label' => 'Pengembalian',  'href' => '../Pengembalian/pengembalian.php',     'icon' => 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3m-9-6h.01'],
    ['id' => 'pengaturan',    'label' => 'Pengaturan',    'href' => '../Akun/pengaturan.php',               'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
];
?>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="theme-color" content="#4f46e5" />
  <title><?= htmlspecialchars($subUser) ?> &middot; PUSAKU</title>

  <!-- Favicon PUSAKU -->
  <link rel="icon" type="image/svg+xml" href="<?= $urlPrefix ?>Assets/logo/favicon.svg" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $urlPrefix ?>Assets/logo/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $urlPrefix ?>Assets/logo/favicon-16.png" />
  <link rel="apple-touch-icon" href="<?= $urlPrefix ?>Assets/logo/favicon.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $urlPrefix ?>Assets/css/pusaku.css" />
</head>

<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased">

  <!-- Overlay untuk sidebar di mobile -->
  <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

  <!-- ================= SIDEBAR ================= -->
  <aside data-sidebar
         class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white
                transition-transform duration-300 ease-out lg:translate-x-0">

    <!-- Brand: logo resmi PUSAKU (horizontal lockup) -->
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-5">
      <a href="../Dashboard/dashboard.php" class="flex min-w-0 items-center gap-3">
        <img src="<?= $urlPrefix ?>Assets/logo/pusaku-logo.svg" alt="PUSAKU"
             class="h-8 w-auto shrink-0" />
        <span class="min-w-0 border-l border-slate-200 pl-3">
          <span class="block truncate text-[11px] font-medium uppercase tracking-wider text-slate-400">
            Panel <?= htmlspecialchars($subUser) ?>
          </span>
        </span>
      </a>

      <button type="button" data-sidebar-toggle
              class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
              aria-label="Tutup menu">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Menu -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
      <?php foreach ($menu as $m):
        $aktif = $menuAktif === $m['id']; ?>
        <a href="<?= $m['href'] ?>"
           <?= $aktif ? 'aria-current="page"' : '' ?>
           class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                  <?= $aktif
                      ? 'bg-brand-50 text-brand-700'
                      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
          <svg class="h-5 w-5 shrink-0 <?= $aktif ? 'text-brand-600' : 'text-slate-400 group-hover:text-slate-600' ?>"
               fill="none" viewBox="0 0 24 24" stroke-width="<?= $aktif ? '2' : '1.7' ?>" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $m['icon'] ?>" />
          </svg>
          <?= htmlspecialchars($m['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Kartu user + logout -->
    <div class="shrink-0 border-t border-slate-200 p-3">
      <div class="flex items-center gap-3 rounded-xl p-2">
        <?php
        // Foto profil disimpan di Assets/uploads/profil/ dengan nama acak.
        // urlFoto() juga memfilter nama yang tidak sesuai pola milik kita.
        $urlFotoUser = \App\Services\Profil::urlFoto($fotoUser, $urlPrefix);
        ?>
        <?php if ($urlFotoUser): ?>
          <img src="<?= htmlspecialchars($urlFotoUser) ?>"
               alt="Foto profil" class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-white" />
        <?php else: ?>
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
            <?= htmlspecialchars(strtoupper(mb_substr($namaUser, 0, 1))) ?>
          </span>
        <?php endif; ?>

        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900"><?= htmlspecialchars($namaUser) ?></p>
          <p class="truncate text-xs text-slate-500"><?= htmlspecialchars($subUser) ?></p>
        </div>
      </div>

      <a href="<?= $urlLogout ?>"
         class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600
                transition hover:bg-rose-50 hover:text-rose-700">
        <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
        </svg>
        Keluar
      </a>
    </div>
  </aside>

  <!-- ================= KONTEN ================= -->
  <div class="lg:pl-72">

    <!-- Topbar mobile -->
    <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/85 px-4 backdrop-blur-lg lg:hidden">
      <button type="button" data-sidebar-toggle
              class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100"
              aria-label="Buka menu">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>
      <img src="<?= $urlPrefix ?>Assets/logo/pusaku-logo.svg" alt="PUSAKU"
           class="h-7 w-auto shrink-0" />
    </header>

    <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

      <?php require __DIR__ . '/flash.php'; ?>
