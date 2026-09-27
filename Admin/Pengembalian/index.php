<?php
/**
 * Pengembalian Cepat (halaman proses).
 *
 * Seluruh chrome (head, sidebar, topbar) sekarang memakai shell bersama
 * Admin/Layouts/header.php + footer.php. Logika PHP, ID elemen, dan nama
 * fungsi TIDAK BERUBAH.
 */
session_start();  // Memastikan sesi dimulai
require_once '../../Config/koneksi.php';  // Menghubungkan ke file koneksi database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_pinjam = $_POST['kode_pinjam'];
    $kondisi_buku = $_POST['kondisi_buku']; // Menambahkan input kondisi buku
    $tgl_kembali = date('Y-m-d'); // Tanggal saat ini sebagai tanggal kembali
    $denda = 0;
    $status_pembayaran = 'Tidak Ada'; // Default pembayaran tidak ada
    $status = 'Lunas'; // Default status lunas

    // Ambil data peminjaman berdasarkan kode_pinjam
    $stmt = $conn->prepare("
        SELECT tgl_pinjam, kode_buku, estimasi_pinjam
        FROM peminjaman 
        WHERE kode_pinjam = :kode_pinjam
    ");
    $stmt->execute([':kode_pinjam' => $kode_pinjam]);
    $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$peminjaman) {
        $_SESSION['message'] = "Kode peminjaman tidak ditemukan.";
        header('Location: pengembalian.php');
        exit;
    }

    $kode_buku = $peminjaman['kode_buku']; // Ambil ID buku untuk pembaruan stok
    $estimasi_pinjam = $peminjaman['estimasi_pinjam']; // Ambil estimasi pinjam

    // Hitung keterlambatan
    $hari_terlambat = max((strtotime($tgl_kembali) - strtotime($estimasi_pinjam)) / (60 * 60 * 24), 0);

    // Jika tidak terlambat, set status dan pembayaran
    if ($hari_terlambat <= 0) {
        $status = 'Lunas';
        $status_pembayaran = 'Tidak Ada'; // Tidak ada pembayaran jika tidak terlambat
    } else {
        $status = 'Belum Lunas';
        $denda += $hari_terlambat * 5000; // Tambahkan denda Rp5.000 per hari keterlambatan
    }

    // Hitung denda tambahan berdasarkan kondisi buku
    if ($kondisi_buku === 'rusak') {
        $denda += 20000; // Denda Rp20.000 untuk buku rusak
        $status = 'Belum Lunas'; // Harus belum lunas jika buku rusak
    } elseif ($kondisi_buku === 'hilang') {
        $denda += 50000; // Denda Rp50.000 untuk buku hilang
        $status = 'Belum Lunas'; // Harus belum lunas jika buku hilang
    }

    // Generate kode_kembali otomatis
    $lastKode = $conn->query("
        SELECT kode_kembali 
        FROM pengembalian 
        ORDER BY kode_kembali DESC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    $newNumber = isset($lastKode['kode_kembali']) ? (int)substr($lastKode['kode_kembali'], 2) + 1 : 1;
    $kode_kembali = "KB" . str_pad($newNumber, 3, "0", STR_PAD_LEFT);

    // Simpan ke tabel pengembalian
    $stmt = $conn->prepare("
        INSERT INTO pengembalian (kode_kembali, kode_pinjam, tgl_kembali, kondisi_buku, denda, status, pembayaran) 
        VALUES (:kode_kembali, :kode_pinjam, :tgl_kembali, :kondisi_buku, :denda, :status, :pembayaran)
    ");
    try {
        $stmt->execute([
            ':kode_kembali' => $kode_kembali,
            ':kode_pinjam' => $kode_pinjam,
            ':tgl_kembali' => $tgl_kembali,
            ':kondisi_buku' => $kondisi_buku,
            ':denda' => $denda,
            ':status' => $status,
            ':pembayaran' => $status_pembayaran
        ]);

        // Pembaruan stok buku berdasarkan kondisi buku
        if ($kondisi_buku === 'bagus' || $kondisi_buku === 'rusak') {
            $conn->prepare("
                UPDATE buku 
                SET stok = stok + 1 
                WHERE kode_buku = :kode_buku
            ")->execute([':kode_buku' => $kode_buku]);
        }
        // Jika kondisi buku hilang, stok tidak diubah

        $_SESSION['message'] = "Pengembalian berhasil ditambahkan! Kode Kembali: $kode_kembali, Denda: Rp" . number_format($denda, 0, ',', '.');
        header('Location: pengembalian.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Gagal menambahkan pengembalian: " . $e->getMessage();
        header('Location: pengembalian.php');
        exit;
    }
}

// Ambil data peminjaman yang belum dikembalikan
$peminjaman = $conn->query("
    SELECT kode_pinjam 
    FROM peminjaman 
    WHERE kode_pinjam NOT IN (SELECT kode_pinjam FROM pengembalian)
")->fetchAll(PDO::FETCH_ASSOC);

$menuAktif = 'pengembalian';
include '../Layouts/header.php';
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Proses Pengembalian</h1>
    <p class="text-muted mt-1">
      <?= number_format(count($peminjaman), 0, ',', '.') ?> peminjaman belum dikembalikan
    </p>
  </div>

  <a href="pengembalian.php" class="btn-secondary btn-sm">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
    </svg>
    Kembali ke Data Pengembalian
  </a>
</section>

<!-- ================= FORM PROSES ================= -->
<section class="card-base mt-6">
  <div class="border-b border-slate-200 px-6 py-4">
    <h2 class="text-section">Form Pengembalian</h2>
    <p class="text-muted mt-1">Masukkan kode peminjaman, lalu tentukan kondisi buku saat dikembalikan.</p>
  </div>

  <div class="p-6">
    <form action="add_pengembalian.php" method="POST" class="max-w-xl space-y-5" novalidate>

      <!-- Pencarian Kode Peminjaman -->
      <div class="autocomplete">
        <label for="kode_pinjam" class="field-label">Kode Peminjaman <span class="text-rose-500">*</span></label>
        <div class="relative">
          <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
               fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
          <input type="text" id="kode_pinjam" name="kode_pinjam" class="input-icon"
                 placeholder="Contoh: PN001" autocomplete="off" data-autofocus required />
        </div>
        <div id="search_results" class="autocomplete-panel"></div> <!-- Menampilkan hasil pencarian -->
        <p class="field-hint">Ketik kode peminjaman atau nama anggota.</p>
      </div>

      <!-- Kondisi Buku -->
      <div>
        <label for="kondisi_buku" class="field-label">Kondisi Buku <span class="text-rose-500">*</span></label>
        <select name="kondisi_buku" id="kondisi_buku" class="field-select" required>
          <option value="bagus">Bagus</option>
          <option value="rusak">Rusak</option>
          <option value="hilang">Hilang</option>
        </select>
      </div>

      <div class="alert-neutral">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <p>Denda keterlambatan dihitung otomatis, ditambah denda tambahan bila buku rusak atau hilang.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5 border-t border-slate-200 pt-5">
        <button type="submit" class="btn-primary btn-sm">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3m-9-6h.01" />
          </svg>
          Proses Pengembalian
        </button>
      </div>
    </form>
  </div>
</section>

<script>
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

  /* Panggil setupAutocomplete saat halaman dimuat */
  document.addEventListener('DOMContentLoaded', setupAutocomplete);
</script>

<?php include '../Layouts/footer.php'; ?>
