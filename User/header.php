<?php
/**
 * Layout anggota (member) - Pusaku
 *
 * YANG DIPERTAHANKAN (tidak diubah):
 *  - session_start() + proteksi login
 *  - require_once koneksi database
 *  - $page untuk penanda menu aktif
 *  - URL menu: home.php, history.php, account.php
 *
 * YANG DIUBAH: markup + CSS saja (Bootstrap -> Tailwind).
 */
// Memuat kernel lebih dulu: mendaftarkan autoloader (App\Services\...)
// dan memulai session bila belum. bootstrap.php aman dipanggil walau
// session sudah aktif (halaman yang sudah me-require-nya sebelumnya).
require_once __DIR__ . '/../Config/bootstrap.php';
require_once __DIR__ . '/../Config/koneksi.php';

if (!isset($_SESSION['nim'])) {
  header('Location: ../login.php');
  exit;
}

$page = basename($_SERVER['PHP_SELF'], '.php');

$menu = [
  ['id' => 'home', 'label' => 'Beranda', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75', 'href' => 'home.php'],
  ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z', 'href' => 'dashboard.php'],
  ['id' => 'history', 'label' => 'Riwayat', 'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25', 'href' => 'history.php'],
  ['id' => 'account', 'label' => 'Akun', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z', 'href' => 'account.php'],
];

// Perbaiki penanda aktif: versi lama memakai 'akun' sehingga halaman
// account.php tidak pernah tampil sebagai aktif.
$activeId = match ($page) {
  'home' => 'home',
  'dashboard' => 'dashboard',
  'history' => 'history',
  'account' => 'account',
  default => '',
};
?>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="theme-color" content="#4f46e5" />
  <title>PUSAKU - Perpustakaan Digital</title>

  <!-- Favicon PUSAKU -->
  <link rel="icon" type="image/svg+xml" href="../Assets/logo/favicon.svg" />
  <link rel="icon" type="image/png" sizes="32x32" href="../Assets/logo/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="../Assets/logo/favicon-16.png" />
  <link rel="apple-touch-icon" href="../Assets/logo/favicon.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="../Assets/css/pusaku.css" />
</head>

<body class="min-h-full pb-24 lg:pb-0">

  <!-- ================= HEADER ================= -->
  <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur-lg">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">

      <!-- Brand: logo resmi PUSAKU -->
      <a href="home.php" class="flex shrink-0 items-center">
        <img src="../Assets/logo/pusaku-logo.svg" alt="PUSAKU" class="h-8 w-auto sm:h-9" />
      </a>

      <!-- Navigasi desktop -->
      <nav class="ml-4 hidden items-center gap-1 lg:flex">
        <?php foreach ($menu as $m): ?>
          <a href="<?= $m['href'] ?>"
             class="rounded-lg px-3 py-2 text-sm font-medium transition
                    <?= $activeId === $m['id']
                        ? 'bg-brand-50 text-brand-700'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' ?>">
            <?= $m['label'] ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="ml-auto flex items-center gap-2">
        <span class="hidden text-sm text-slate-500 sm:inline"><?= htmlspecialchars((string) ($_SESSION['nama'] ?? '')) ?></span>
        <?php
        // Avatar: foto profil bila ada, selain itu inisial.
        $fotoProfil = \App\Services\Profil::urlFoto($_SESSION['profil_gambar'] ?? null, '../');
        $inisialUser = strtoupper(mb_substr((string) ($_SESSION['nama'] ?? '?'), 0, 1));
        ?>
        <a href="account.php"
           class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full
                  bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200"
           aria-label="Akun saya">
          <?php if ($fotoProfil): ?>
            <img src="<?= htmlspecialchars($fotoProfil) ?>" alt="" class="h-full w-full object-cover" />
          <?php else: ?>
            <span class="text-sm font-semibold"><?= htmlspecialchars($inisialUser) ?></span>
          <?php endif; ?>
        </a>
        <!-- Logout: ke file tunggal di root project (../logout.php) -->
        <a href="../logout.php"
           data-confirm="Yakin ingin keluar dari akun Anda?"
           class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600
                  ring-1 ring-inset ring-slate-200 transition hover:bg-rose-50 hover:text-rose-600"
           aria-label="Keluar">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
          </svg>
        </a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <?php require __DIR__ . '/../Config/layouts/flash.php'; ?>

    <!-- ================= KONTEN (diisi oleh halaman) ================= -->

