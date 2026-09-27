<?php
/**
 * Penutup layout anggota. Menyertakan bottom tab bar untuk mobile
 * dan menutup tag HTML.
 *
 * Dipasangkan dengan User/header.php.
 */
$pageFooter = basename($_SERVER['PHP_SELF'], '.php');

$menuFooter = [
  ['id' => 'home', 'label' => 'Beranda', 'href' => 'home.php', 'icon' => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
  ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z'],
  ['id' => 'history', 'label' => 'Riwayat', 'href' => 'history.php', 'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
  ['id' => 'account', 'label' => 'Akun', 'href' => 'account.php', 'icon' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
];

$aktifFooter = match ($pageFooter) {
  'home' => 'home',
  'dashboard' => 'dashboard',
  'history' => 'history',
  'account' => 'account',
  default => '',
};
?>
  </main>

  <footer class="mt-12 border-t border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
      <p class="flex items-center gap-2">
        <img src="../Assets/logo/pusaku-icon.svg" alt="" class="h-4 w-4 shrink-0" />
        <span>&copy; <?= date('Y') ?> PUSAKU &middot; Sistem Informasi Perpustakaan</span>
      </p>
      <p> Dibangun dengan PHP &amp; Tailwind CSS</p>
    </div>
  </footer>

  <!-- Bottom tab bar: hanya di mobile, thumbs reach zone -->
  <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-safe backdrop-blur-lg lg:hidden">
    <div class="mx-auto flex max-w-lg items-stretch">
      <?php foreach ($menuFooter as $m): ?>
        <a href="<?= $m['href'] ?>"
           class="flex flex-1 flex-col items-center gap-1 py-2.5 text-[11px] font-medium transition
                  <?= $aktifFooter === $m['id'] ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700' ?>"
           <?= $aktifFooter === $m['id'] ? 'aria-current="page"' : '' ?>>
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="<?= $aktifFooter === $m['id'] ? '2' : '1.6' ?>" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $m['icon'] ?>" />
          </svg>
          <?= $m['label'] ?>
        </a>
      <?php endforeach; ?>
    </div>
  </nav>

</body>

</html>
