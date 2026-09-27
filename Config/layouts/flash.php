<?php
/**
 * Banner pesan flash.
 *
 * Satu-satunya tempat pesan "flash" ditampilkan. Dipakai oleh shell Admin &
 * Owner (Config/layouts/shell_start.php) dan panel User
 * (User/header.php), jadi setiap halaman yang memanggil flash() + redirect
 * otomatis menampilkan notifikasinya - tanpa perlu menulis markup sendiri.
 *
 * Format: flash('success'|'danger'|'warning'|'info', 'pesan')
 *
 * CATATAN: pesan ini menggantikan dialog alert bawaan browser yang dulu dipakai
 * di banyak file. Notifikasi sekarang non-blocking: user bisa tetap
 *membaca pesan tanpa menekan tombol OK.
 *
 * @var string|null $tipe   inherited dari pemanggil
 * @var string|null $pesan  inherited dari pemanggil
 */
if (!function_exists('ambil_flash')) {
    // Halaman yang belum memuat kernel (mis. print) tidak punya flash.
    return;
}

$flashTipe  = $tipe  ?? null;
$flashPesan = $pesan ?? null;

if (!$flashTipe || !$flashPesan) {
    $flash = ambil_flash();
    $flashTipe  = $flash['tipe']  ?? null;
    $flashPesan = $flash['pesan'] ?? null;
}
?>
<?php if ($flashTipe && $flashPesan): ?>
  <?php
  $cls = match ($flashTipe) {
      'success' => 'alert-success',
      'danger'  => 'alert-danger',
      'warning' => 'alert-warning',
      default   => 'alert-info',
  };
  $ikon = match ($flashTipe) {
      'success' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
      'danger'  => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
      'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
      default   => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
  };
  ?>
  <div role="status" class="mb-6" id="flashBanner">
    <div class="<?= $cls ?>">
      <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $ikon ?>" />
      </svg>
      <p class="flex-1 font-medium"><?= e($flashPesan) ?></p>
      <button type="button" data-flash-tutup
              class="shrink-0 rounded-lg p-1.5 opacity-60 transition hover:bg-black/5 hover:opacity-100"
              aria-label="Tutup notifikasi">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
  <script>
    (function () {
      var b = document.getElementById('flashBanner');
      if (!b) return;
      b.querySelector('[data-flash-tutup]')?.addEventListener('click', function () { b.remove(); });
      // Hilang sendiri setelah 6 detik supaya tidak menghalangi konten.
      setTimeout(function () {
        b.style.transition = 'opacity .4s, transform .4s';
        b.style.opacity = '0';
        b.style.transform = 'translateY(-6px)';
        setTimeout(function () { b.remove(); }, 420);
      }, 6000);
    })();
  </script>
<?php endif; ?>
