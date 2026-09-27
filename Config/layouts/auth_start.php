<?php
/**
 * Shell bersama untuk halaman autentikasi (User, Admin, Owner).
 *
 * Kontrak variabel yang harus diisi sebelum include:
 *   $peranLabel : 'Anggota' | 'Admin' | 'Owner'
 *   $heading    : judul besar di panel kiri
 *   $subheading : kalimat di bawahnya
 *   $accent     : 'brand' | 'emerald' | 'violet'
 *   $judulForm  : judul form di panel kanan
 *   $subForm    : keterangan form
 *   $judulDoc   : <title>
 *   $urlPrefix  : '../../' relatif ke lokasi file
 *
 * Variabel opsional untuk angka sorotan:
 *   $statBuku, $statAnggota, $statPetugas
 *
 * Panel kiri disembunyikan di layar kecil agar form tidak terdorong
 * ke bawah pada ponsel.
 */

/**
 * Definisi aksen per peran.
 *
 * `glow1`/`glow2` dipakai di dalam atribut style (gradient radial), jadi
 * nilainya harus berupa hex, bukan kelas Tailwind. Sisanya adalah kelas.
 */
$aksen = [
    'brand' => [
        'glow1' => '#4f46e5',
        'glow2' => '#0ea5e9',
        'orb1'  => 'bg-brand-500/40',
        'orb2'  => 'bg-sky-400/25',
        'dot'   => 'bg-brand-200',
        'kartu' => 'text-brand-600 bg-brand-50 ring-brand-500/20',
        'tautan'=> 'text-brand-600 hover:text-brand-700',
        'tombol'=> 'btn-primary',
    ],
    'emerald' => [
        'glow1' => '#059669',
        'glow2' => '#0d9488',
        'orb1'  => 'bg-emerald-400/35',
        'orb2'  => 'bg-teal-300/25',
        'dot'   => 'bg-emerald-200',
        'kartu' => 'text-emerald-600 bg-emerald-50 ring-emerald-500/20',
        'tautan'=> 'text-emerald-600 hover:text-emerald-700',
        'tombol'=> 'btn-success',
    ],
    'violet' => [
        'glow1' => '#7c3aed',
        'glow2' => '#c026d3',
        'orb1'  => 'bg-violet-500/40',
        'orb2' => 'bg-fuchsia-400/25',
        'dot'   => 'bg-violet-200',
        'kartu' => 'text-violet-600 bg-violet-50 ring-violet-500/20',
        'tautan'=> 'text-violet-600 hover:text-violet-700',
        'tombol'=> 'btn-violet',
    ],
][$accent ?? 'brand'] ?? [];
$accent = $accent ?? 'brand';
$aksen = $aksen ?: [
    'glow1' => '#4f46e5', 'glow2' => '#0ea5e9', 'orb1' => 'bg-brand-500/40',
    'orb2' => 'bg-sky-400/25', 'dot' => 'bg-brand-200',
    'kartu' => 'text-brand-600 bg-brand-50 ring-brand-500/20',
    'tautan' => 'text-brand-600 hover:text-brand-700',
    'tombol' => 'btn-primary',
];

/**
 * Kelas tombol submit, mengikuti warna aksen portal.
 * Halaman login memakainya di tombolnya supaya warna tombol_selalu
 * senada dengan panel kiri.
 */
$tombolSubmit = $aksen['tombol'];

$peranLabel = $peranLabel ?? 'Pengguna';
$heading    = $heading    ?? 'Selamat Datang';
$subheading = $subheading ?? 'Silakan masuk untuk melanjutkan.';
$judulForm  = $judulForm  ?? 'Masuk ke akun Anda';
$subForm    = $subForm    ?? 'Gunakan kredensial yang terdaftar untuk melanjutkan.';
$judulDoc   = $judulDoc   ?? 'Login - Pusaku';
$urlPrefix  = $urlPrefix  ?? '../../';
?><!DOCTYPE html>
<html lang="id" class="h-full">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="theme-color" content="#4f46e5" />
  <title><?= htmlspecialchars($judulDoc) ?></title>

  <!-- Favicon PUSAKU -->
  <link rel="icon" type="image/svg+xml" href="<?= $urlPrefix ?>Assets/logo/favicon.svg" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $urlPrefix ?>Assets/logo/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $urlPrefix ?>Assets/logo/favicon-16.png" />
  <link rel="apple-touch-icon" href="<?= $urlPrefix ?>Assets/logo/favicon.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= $urlPrefix ?>Assets/css/pusaku.css" />

  <!-- SweetAlert2: dialog salam "selamat datang" + pesan gagal login.
       Deferred di head supaya sudah siap dipakai Assets/js/login.js. -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js" defer></script>
</head>

<body class="min-h-full bg-white font-sans text-slate-900 antialiased">

<div class="flex min-h-screen">

  <!-- ================= PANEL KIRI (brand) ================= -->
  <!--
    Split baru dibuka di breakpoint `xl` (1280px), bukan `lg` (1024px).

    Alasannya: pada 1024px layar terbagi 50/50 jadi panel kanan hanya
    512px. Setelah dikurangi padding, ruang untuk form tinggal ~384px -
    kartu form menyusut dari 448px ke 384px tepat saat panel kiri muncul,
    sehingga tampilan meloncat. Di bawah 1280px kita pakai satu kolom
    terpusat, yang jauh lebih lapang dan rapi.
  -->
  <div class="relative hidden w-1/2 shrink-0 overflow-hidden bg-slate-950 xl:flex xl:flex-col">

    <!-- Ilustrasi perpustakaan (SVG 1:1) sebagai latar panel.
         Dipakai background-position: right supaya saat panel lebih tinggi
         dari 1:1, area tenang di sisi kiri (tempat teks putih) tetap
         diprioritaskan dan bagian yang terpotong justru bagian ramai. -->
    <div class="absolute inset-0 bg-cover bg-no-repeat bg-right"
         style="background-image:url('<?= $urlPrefix ?>Assets/img/perpustakaan-illustrasi.svg')"></div>

    <!-- Scrim: menjaga kontras teks putih di sisi kiri.
         Kuat sampai ~55% lebar karena blok judul + statistik memakai
         max-w-md, jadi area tenantsinya lebih luas dari sekadar separuh
         panel. Sisanya diturunkan bertahap supaya ilustrasi tetap terlihat. -->
    <div class="absolute inset-0"
         style="background:linear-gradient(100deg,rgba(5,8,22,.97) 0%,rgba(5,8,22,.94) 30%,rgba(5,8,22,.76) 52%,rgba(5,8,22,.34) 74%,rgba(5,8,22,.08) 100%)"></div>

    <!-- Aksen warna portal, di atas ilustrasi dan tetap tipis. -->
    <div class="absolute inset-0 opacity-55"
         style="background:radial-gradient(115% 90% at 12% 8%, <?= $aksen['glow1'] ?> 0%, transparent 58%),radial-gradient(95% 85% at 88% 92%, <?= $aksen['glow2'] ?> 0%, transparent 52%)"></div>

    <!-- Glow lembut -->
    <div class="absolute -left-28 -top-24 h-[30rem] w-[30rem] rounded-full <?= $aksen['orb1'] ?> opacity-40 blur-3xl"></div>
    <div class="absolute -bottom-32 -right-20 h-[26rem] w-[26rem] rounded-full <?= $aksen['orb2'] ?> opacity-30 blur-3xl"></div>

    <!-- Vignette supaya bagian bawah tidak terlalu terang -->
    <div class="absolute inset-0"
         style="background:linear-gradient(to top,rgba(2,6,23,.70) 0%,transparent 45%)"></div>

    <div class="relative flex h-full flex-col justify-between p-12 xl:p-16">

      <!-- Brand: logo PUSAKU di atas pil putih.

           Gradient ikon pada logo bersifat mid-tone (#6366F1 -> #8B5CF6).
           Diletakkan langsung di atas gradient indigo panel, ikonnya
           menyatu dan nyaris tak terlihat. Pil putih memberi kontras,
           dan kita pakai varian TERANG (wordmark gelap) karena
               background pil-nya putih. Varian dark (wordmark putih)
               tidak dipakai di sini - akan hilang di atas putih. -->
      <div class="inline-flex self-start rounded-2xl bg-white px-4 py-2.5 shadow-lift">
        <img src="<?= $urlPrefix ?>Assets/logo/pusaku-logo.svg" alt="PUSAKU"
             class="h-9 w-auto" />
      </div>

      <!-- Pesan utama -->
      <div class="max-w-md">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10
                     px-3.5 py-1.5 text-xs font-semibold text-white backdrop-blur">
          <span class="h-1.5 w-1.5 animate-pulse rounded-full <?= $aksen['dot'] ?>"></span>
          Portal <?= htmlspecialchars($peranLabel) ?>
        </span>

        <h2 class="mt-7 text-4xl font-extrabold leading-[1.12] tracking-tight text-white xl:text-[3.25rem]">
          <?= htmlspecialchars($heading) ?>
        </h2>

        <p class="mt-5 text-base leading-relaxed text-white/65">
          <?= htmlspecialchars($subheading) ?>
        </p>

        <!-- Angka sorotan -->
        <dl class="mt-10 grid grid-cols-3 gap-3">
          <?php
          $sorotan = [
              'Koleksi' => $statBuku    ?? 0,
              'Anggota' => $statAnggota ?? 0,
              'Petugas' => $statPetugas ?? 0,
          ];
          foreach ($sorotan as $label => $nilai): ?>
            <div class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3.5 backdrop-blur">
              <dt class="text-[10px] font-semibold uppercase tracking-wider text-white/45"><?= $label ?></dt>
              <dd class="mt-1 text-2xl font-bold tracking-tight text-white">
                <?= number_format((float) $nilai, 0, ',', '.') ?>
              </dd>
            </div>
          <?php endforeach; ?>
        </dl>
      </div>

      <p class="text-xs text-white/35">
        &copy; <?= date('Y') ?> Pusaku &middot; Sistem Informasi Perpustakaan Universitas
      </p>
    </div>
  </div>

  <!-- ================= PANEL KANAN (form) ================= -->
  <div class="relative flex w-full flex-col justify-center overflow-hidden bg-slate-100/80
              px-5 py-10 sm:px-8 xl:w-1/2 xl:px-16 2xl:px-24">

    <!-- Wash lembut di mode satu kolom supaya tidak terlihat polos kosong.
         Sengaja sangat tipis: hanya sebagai latar, bukan focal point.
         Sembunyi saat panel kiri sudah tampil. -->

    <div class="pointer-events-none absolute inset-0 opacity-[0.07] xl:hidden"
         style="background:radial-gradient(75% 50% at 50% 0%, <?= $aksen['glow1'] ?> 0%, transparent 65%)"></div>

    <div class="relative mx-auto w-full max-w-md">

      <!-- Brand mobile: versi terang (wordmark gelap) karena background putih.
           xl:hidden wajib ada, jika tidak logo ini ikut tampil berdampingan
           dengan logo gelap panel kiri di layar >=1280px. -->
      <div class="mb-8 flex items-center justify-center xl:hidden">
        <img src="<?= $urlPrefix ?>Assets/logo/pusaku-logo.svg" alt="PUSAKU"
             class="h-10 w-auto" />
      </div>

      <!-- Kartu form -->
      <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-card sm:p-8">
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset <?= $aksen['kartu'] ?>">
          <?= htmlspecialchars($peranLabel) ?>
        </span>

        <h1 class="mt-4 text-[1.75rem] font-extrabold leading-tight tracking-tight text-slate-900">
          <?= htmlspecialchars($judulForm) ?>
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500"><?= htmlspecialchars($subForm) ?></p>

        <?php if (!empty($error)): ?>
          <div role="alert"
               class="mt-6 flex items-start gap-3 rounded-xl bg-rose-50 px-4 py-3.5 text-sm ring-1 ring-inset ring-rose-600/20">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <p class="font-medium text-rose-800"><?= htmlspecialchars($error) ?></p>
          </div>
        <?php endif; ?>

        <form method="POST" action="" class="mt-7 space-y-5" novalidate data-login-form>
