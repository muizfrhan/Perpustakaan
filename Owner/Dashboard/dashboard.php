<?php
/**
 * Dashboard Owner.
 *
 * QUERY dan nama fungsi (confirmLogout, logout, displayRandomImage,
 * renderCalendar) TIDAK BERUBAH. Yang diubah hanya markup + CSS.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Pastikan ada sesi untuk ID owner.
// WAJIB dievaluasi SEBELUM layout ikut di-include: header() hanya bisa
// dipanggil sebelum ada output HTML, jika tidak akan memunculkan warning
// "Cannot modify header information - headers already sent".
if (!isset($_SESSION['id_owner'])) {
    header('Location: ../../login.php');
    exit();
}

$menuAktif = 'dashboard';
include '../Layouts/header.php';

// Query untuk mendapatkan jumlah anggota, buku, peminjaman, dan pengembalian
$anggotaResult = $conn->query("SELECT * FROM anggota");
$bukuResult = $conn->query("SELECT * FROM buku");
$peminjamanResult = $conn->query("SELECT * FROM peminjaman");
$pengembalianResult = $conn->query("SELECT * FROM pengembalian");

$id_owner = $_SESSION['id_owner']; // Ambil ID owner dari sesi

// Query untuk mendapatkan nama owner
$query = "SELECT nama_pemilik, profil_gambar FROM owner WHERE id_owner = :id_owner";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_owner', $id_owner, PDO::PARAM_INT);
$stmt->execute();

$nama_pemilik = "Tidak Diketahui"; // Default jika tidak ditemukan
$profil_gambar = '';

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nama_pemilik = htmlspecialchars($row['nama_pemilik']);
    $profil_gambar = (string) $row['profil_gambar'];
}

// Foto profil disimpan di Assets/uploads/profil/ dengan nama acak.
// urlFoto() menolak nama yang tidak sesuai pola itu.
$urlFotoProfil = App\Services\Profil::urlFoto($profil_gambar, '../../');

// Tanggal otomatis sesuai login
$tanggal_hari_ini = date('jS F Y'); // Format: 14th Aug 2023

// Query untuk peminjaman yang perlu dikembalikan
$queryPinjamKembali = "
    SELECT p.kode_pinjam, a.nama, p.kode_buku, p.estimasi_pinjam
    FROM peminjaman p
    JOIN anggota a ON p.nim = a.nim
    WHERE p.estimasi_pinjam < CURDATE() AND p.status = 'Dipinjam'
";
$stmtPinjamKembali = $conn->query($queryPinjamKembali);

// Query untuk pengembalian dengan status belum lunas
$queryBelumLunas = "
    SELECT pk.kode_kembali, a.nama, pk.denda, pk.pembayaran
    FROM pengembalian pk
    JOIN peminjaman p ON pk.kode_pinjam = p.kode_pinjam
    JOIN anggota a ON p.nim = a.nim
    WHERE pk.status = 'Belum Lunas'
";
$stmtBelumLunas = $conn->query($queryBelumLunas);

$kartuStatistik = [
    [
        'label' => 'Anggota',
        'nilai' => $anggotaResult->rowCount(),
        'href'  => '../Anggota/anggota.php',
        'ikon'  => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'warna' => 'bg-brand-50 text-brand-600',
    ],
    [
        'label' => 'Buku',
        'nilai' => $bukuResult->rowCount(),
        'href'  => '../Buku/buku.php',
        'ikon'  => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'warna' => 'bg-violet-50 text-violet-600',
    ],
    [
        'label' => 'Peminjaman',
        'nilai' => $peminjamanResult->rowCount(),
        'href'  => '../Peminjaman/peminjaman.php',
        'ikon'  => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 006 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'warna' => 'bg-amber-50 text-amber-600',
    ],
    [
        'label' => 'Pengembalian',
        'nilai' => $pengembalianResult->rowCount(),
        'href'  => '../Pengembalian/pengembalian.php',
        'ikon'  => 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3m-9-6h.01',
        'warna' => 'bg-emerald-50 text-emerald-600',
    ],
];
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Dashboard</h1>
    <p class="text-muted mt-1"><?= $tanggal_hari_ini; ?></p>
  </div>

  <div class="flex items-center gap-3">
    <button type="button" onclick="confirmLogout()" class="btn-secondary btn-sm"
            aria-label="Keluar dari akun">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
      </svg>
      Keluar
    </button>

    <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
      <?php if ($urlFotoProfil): ?>
        <img src="<?= $urlFotoProfil ?>" alt="Foto profil"
             class="h-11 w-11 rounded-full object-cover ring-2 ring-white" />
      <?php else: ?>
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
          <?= htmlspecialchars(strtoupper(mb_substr($nama_pemilik, 0, 1))) ?>
        </span>
      <?php endif; ?>
      <div class="hidden sm:block">
        <p class="text-sm font-semibold leading-tight text-slate-900"><?= $nama_pemilik ?></p>
        <p class="text-xs text-slate-500">Pemilik</p>
      </div>
    </div>
  </div>
</section>

<!-- ================= KARTU STATISTIK ================= -->
<!-- 2 kolom mulai 360px. Di bawah itu 1 kolom: pada 320px tiap kartu hanya
     ~136px sehingga ikon + label panjang seperti "PENGEMBALIAN" tidak muat
     dan ikon terdorong keluar kartu (overflow horizontal).
     min-w-0 + ikon 36px + label 10px menjaga dua kolom tetap aman di 360px. -->
<section class="mt-6 grid grid-cols-1 gap-4 min-[360px]:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($kartuStatistik as $k): ?>
    <a href="<?= $k['href'] ?>" class="card-base card-hover group p-4 sm:p-5">
      <div class="flex items-start justify-between gap-2 sm:gap-4">
        <div class="min-w-0">
          <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400 sm:text-label">
            <?= htmlspecialchars($k['label']) ?>
          </p>
          <p class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
            <?= number_format((int) $k['nilai'], 0, ',', '.') ?>
          </p>
        </div>
        <span class="stat-icon <?= $k['warna'] ?> h-9 w-9 sm:h-12 sm:w-12">
          <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $k['ikon'] ?>" />
          </svg>
        </span>
      </div>

      <span class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-slate-400
                   transition group-hover:text-brand-600">
        Lihat semua
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
      </span>
    </a>
  <?php endforeach; ?>
</section>

<!-- ================= KALENDER + DAFTAR TINDAKAN ================= -->
<section class="mt-6 grid items-start gap-6 xl:grid-cols-2">

  <!-- Kalender -->
  <div class="card-base p-4 sm:p-5">
    <div class="flex items-center justify-between gap-3">
      <h2 id="month-year" class="text-base font-semibold tracking-tight text-slate-900">&nbsp;</h2>
      <div class="flex items-center gap-1">
        <button type="button" id="prev" class="btn-secondary h-8 w-8 rounded-lg p-0" aria-label="Bulan sebelumnya">
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>
        <button type="button" id="next" class="btn-secondary h-8 w-8 rounded-lg p-0" aria-label="Bulan berikutnya">
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
          </svg>
        </button>
      </div>
    </div>

    <!--
      Grid dibatasi max-w-[17.5rem] supaya sel tetap ~38px, bukan mengikuti
      lebar kartu. Ilustrasi pindah ke samping kalender (bukan di bawahnya)
      supaya tidak menambah tinggi kartu. Di layar kecil ilustrasi disembunyikan
      agar kalender tetap muat dan tidak menyebabkan overflow horizontal.
    -->
    <div class="mt-3 flex items-stretch gap-3 sm:gap-4">
      <div class="calendar-grid mx-auto w-full max-w-[17.5rem] shrink-0 sm:mx-0" id="calendar-grid"></div>

      <div class="relative hidden min-w-0 flex-1 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 sm:block">
        <img id="random-image" src="" alt="Ilustrasi"
             class="absolute inset-0 h-full w-full object-cover opacity-0 transition-opacity duration-500" />
      </div>
    </div>
  </div>

  <!-- Tindakan -->
  <div class="space-y-6">

    <!-- Perlu dikembalikan -->
    <div class="card-base p-5">
      <div class="flex items-start gap-3.5">
        <span class="stat-icon bg-amber-50 text-amber-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 6v6l3.75 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </span>
        <div>
          <h2 class="text-section">Perlu Dikembalikan</h2>
          <p class="text-muted mt-0.5">Daftar buku yang harus segera dikembalikan</p>
        </div>
      </div>

      <div class="divider my-4"></div>

      <?php if ($stmtPinjamKembali->rowCount() > 0): ?>
        <ul class="space-y-2.5">
          <?php while ($row = $stmtPinjamKembali->fetch(PDO::FETCH_ASSOC)): ?>
            <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900">
                  <code class="code-chip"><?= htmlspecialchars($row['kode_pinjam']); ?></code>
                </p>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  <?= htmlspecialchars($row['nama']); ?>
                  &middot; estimasi <?= htmlspecialchars($row['estimasi_pinjam']); ?>
                </p>
              </div>
              <a href="../Peminjaman/peminjaman.php" class="btn-primary btn-sm">Cek</a>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <div class="empty-state py-10">
          <span class="empty-icon">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </span>
          <p class="text-sm text-slate-500">Tidak ada peminjaman yang perlu dikembalikan.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Belum lunas -->
    <div class="card-base p-5">
      <div class="flex items-start gap-3.5">
        <span class="stat-icon bg-rose-50 text-rose-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
          </svg>
        </span>
        <div>
          <h2 class="text-section">Belum Lunas</h2>
          <p class="text-muted mt-0.5">Daftar pengembalian dengan denda belum lunas</p>
        </div>
      </div>

      <div class="divider my-4"></div>

      <?php if ($stmtBelumLunas->rowCount() > 0): ?>
        <ul class="space-y-2.5">
          <?php while ($row = $stmtBelumLunas->fetch(PDO::FETCH_ASSOC)): ?>
            <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-900">
                  <code class="code-chip"><?= htmlspecialchars($row['kode_kembali']); ?></code>
                </p>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  <?= htmlspecialchars($row['nama']); ?>
                  &middot; denda
                  <span class="font-semibold text-rose-600">
                    Rp<?= number_format($row['denda'], 2, ',', '.'); ?>
                  </span>
                </p>
              </div>
              <a href="../Pengembalian/pengembalian.php" class="btn-warning btn-sm">Tinjau</a>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <div class="empty-state py-10">
          <span class="empty-icon">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </span>
          <p class="text-sm text-slate-500">Tidak ada pengembalian yang belum lunas.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ================= MODAL KONFIRMASI LOGOUT ================= -->
<div id="logoutModal" data-modal role="dialog" aria-modal="true" aria-labelledby="logoutModalLabel"
     class="modal-root">
  <div data-modal-backdrop class="modal-backdrop"></div>
  <div class="modal-panel sm:max-w-md">
    <div class="p-6">
      <span class="stat-icon bg-rose-50 text-rose-600">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
        </svg>
      </span>
      <h2 class="mt-4 text-lg font-bold tracking-tight text-slate-900" id="logoutModalLabel">
        Konfirmasi Logout
      </h2>
      <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
        Apakah Anda yakin ingin keluar dari dashboard owner?
      </p>
    </div>
    <div class="modal-footer">
      <button type="button" data-modal-close class="btn-secondary btn-sm">Batal</button>
      <button type="button" onclick="logout()" class="btn-danger btn-sm">Logout</button>
    </div>
  </div>
</div>

<script>
  // Pratinjau satu gambar: resolve dengan url bila bisa dimuat, null bila gagal.
  function preloadGambar(url) {
    return new Promise((resolve) => {
      const probe = new Image();
      probe.onload = () => resolve(url);
      probe.onerror = () => resolve(null);
      probe.src = url;
    });
  }

  // Fungsi untuk menampilkan gambar secara acak
  function displayRandomImage() {
    // Daftar gambar ilustrasi di folder ../../Assets/img
    const images = ["cwe.jpg", "dosen.jpg", "laki.jpg"];

    const imageElement = document.getElementById("random-image");
    if (!imageElement) return;

    const urls = images.map((nama) => `../../Assets/img/${encodeURIComponent(nama)}`);

    // Muat semua gambar lebih dulu di luar layar: yang hilang/rusak langsung
    // dibuang (tidak ada kotak gambar rusak), dan karena sudah di-cache
    // pergantian slide tidak akan kedip.
    Promise.all(urls.map(preloadGambar)).then((hasil) => {
      const slide = hasil.filter(Boolean);

      if (slide.length === 0) {
        imageElement.removeAttribute("src"); // sisakan placeholder netral
        return;
      }

      // Mulai dari gambar acak supaya tiap kunjungan dashboard tidak sama.
      let index = Math.floor(Math.random() * slide.length);
      let slideTimer = null;
      let fadeTimer = null;

      const tampilkan = () => {
        imageElement.src = slide[index];
        imageElement.classList.add("opacity-100");
      };

      // Fade 500ms (duration-500 di markup) -> tunggu gelap -> ganti gambar.
      const next = () => {
        index = (index + 1) % slide.length;
        imageElement.classList.remove("opacity-100");
        clearTimeout(fadeTimer);
        fadeTimer = setTimeout(tampilkan, 500);
      };

      tampilkan();

      // Putar otomatis. Tanpa tombol navigasi dan tanpa pita penanda.
      slideTimer = setInterval(next, 4500);

      // Jeda saat tab disembunyikan, jalan lagi saat user kembali.
      document.addEventListener("visibilitychange", () => {
        clearInterval(slideTimer);
        clearTimeout(fadeTimer);
        if (!document.hidden) {
          slideTimer = setInterval(next, 4500);
        }
      });
    });
  }

  // Jalankan fungsi saat halaman dimuat
  window.addEventListener('load', displayRandomImage);

  // confirmLogout - nama fungsi DIJAGA. Dulu memakai bootstrap.Modal.
  function confirmLogout() {
    Pusaku.openModal('logoutModal');
  }

  function logout() {
    // Ke file logout tunggal di root project.
    window.location.href = "../../logout.php";
  }

  const monthNames = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"
  ];
  const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

  const calendarGrid = document.getElementById("calendar-grid");
  const monthYearLabel = document.getElementById("month-year");
  const prevButton = document.getElementById("prev");
  const nextButton = document.getElementById("next");

  let currentDate = new Date();

  function renderCalendar() {
    calendarGrid.innerHTML = "";

    // Set month and year
    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();
    monthYearLabel.textContent = `${monthNames[month]} ${year}`;

    // Create day headers
    dayNames.forEach(function (day) {
      const dayHeader = document.createElement("div");
      dayHeader.textContent = day;
      dayHeader.classList.add("calendar-day-head");
      calendarGrid.appendChild(dayHeader);
    });

    // First day of the month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Create blank days
    for (let i = 0; i < firstDay; i++) {
      const blankDay = document.createElement("div");
      blankDay.classList.add("calendar-day-blank");
      calendarGrid.appendChild(blankDay);
    }

    // Create actual days
    for (let day = 1; day <= daysInMonth; day++) {
      const dayElement = document.createElement("div");
      dayElement.textContent = day;
      dayElement.classList.add("calendar-day");

      // Highlight current day
      if (
        day === currentDate.getDate() &&
        month === new Date().getMonth() &&
        year === new Date().getFullYear()
      ) {
        dayElement.classList.add("calendar-day-today");
        dayElement.setAttribute("aria-current", "date");
      }

      calendarGrid.appendChild(dayElement);
    }
  }

  // Navigate months
  prevButton.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
  });

  nextButton.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
  });

  // Initial render
  renderCalendar();
</script>

<?php include '../Layouts/footer.php'; ?>
