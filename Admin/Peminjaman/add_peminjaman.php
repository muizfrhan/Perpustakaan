<?php
/**
 * Form Tambah Peminjaman (fragment AJAX).
 *
 * Dimuat ke dalam #modalContent oleh peminjaman.php.
 * Autocomplete #nim -> #search_results dan #kode_buku -> #search_results_buku
 * ditangani oleh setupAutocomplete()/setupAutocompleteBuku() di halaman induk.
 * Nama field TIDAK BERUBAH.
 */
session_start();  // Memastikan sesi dimulai

require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';  // Menghubungkan ke file koneksi database

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nim = $_POST['nim'];
  $kode_buku = $_POST['kode_buku'];
  $id_petugas = $_POST['id_petugas'];
  $tgl_pinjam = $_POST['tgl_pinjam'];
  $estimasi_pinjam = $_POST['estimasi_pinjam'];
  $kondisi_buku_pinjam = $_POST['kondisi_buku_pinjam'];

  // Cek jumlah buku yang sedang dipinjam oleh anggota
  $checkPeminjamanSql = "SELECT COUNT(*) AS total_pinjaman 
                         FROM peminjaman 
                         WHERE nim = :nim AND kode_pinjam NOT IN 
                         (SELECT kode_pinjam FROM pengembalian)";
  $checkPeminjamanStmt = $conn->prepare($checkPeminjamanSql);
  $checkPeminjamanStmt->execute([':nim' => $nim]);
  $result = $checkPeminjamanStmt->fetch(PDO::FETCH_ASSOC);

  if ($result['total_pinjaman'] >= 2) {
    flash('danger', 'Anggota ini sudah meminjam 2 buku. Tidak dapat meminjam lagi.');
    header('Location: peminjaman.php');
    exit;
  }

  // Generate kode_pinjam otomatis
  $lastKode = $conn->query("SELECT kode_pinjam FROM peminjaman ORDER BY kode_pinjam DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  $newNumber = isset($lastKode['kode_pinjam']) ? (int)substr($lastKode['kode_pinjam'], 2) + 1 : 1;
  $kode_pinjam = "PN" . str_pad($newNumber, 3, "0", STR_PAD_LEFT);

  $sql = "INSERT INTO peminjaman (kode_pinjam, nim, kode_buku, id_petugas, tgl_pinjam, estimasi_pinjam, kondisi_buku_pinjam) 
          VALUES (:kode_pinjam, :nim, :kode_buku, :id_petugas, :tgl_pinjam, :estimasi_pinjam, :kondisi_buku_pinjam)";
  $stmt = $conn->prepare($sql);

  try {
    // Insert data peminjaman
    $stmt->execute([
      ':kode_pinjam' => $kode_pinjam,
      ':nim' => $nim,
      ':kode_buku' => $kode_buku,
      ':id_petugas' => $id_petugas,
      ':tgl_pinjam' => $tgl_pinjam,
      ':estimasi_pinjam' => $estimasi_pinjam,
      ':kondisi_buku_pinjam' => $kondisi_buku_pinjam
    ]);

    // Kurangi stok buku
    $updateStokSql = "UPDATE buku SET stok = stok - 1 WHERE kode_buku = :kode_buku";
    $updateStokStmt = $conn->prepare($updateStokSql);
    $updateStokStmt->execute([':kode_buku' => $kode_buku]);

    flash('success', 'Peminjaman berhasil ditambahkan!');
    header('Location: peminjaman.php');
    exit;
  } catch (PDOException $e) {
    flash('danger', 'Gagal menambahkan peminjaman: ' . $e->getMessage());
    header('Location: peminjaman.php');
    exit;
  }
}


// Query untuk mengambil data anggota, buku, dan petugas
$anggota = $conn->query("SELECT nim, nama FROM anggota WHERE status_mhs = 'Aktif'")->fetchAll(PDO::FETCH_ASSOC);
$buku = $conn->query("SELECT kode_buku, judul_buku FROM buku WHERE stok > 0")->fetchAll(PDO::FETCH_ASSOC);
$petugas = $conn->query("SELECT id_petugas, nama_petugas FROM petugas WHERE status = 'Aktif'")->fetchAll(PDO::FETCH_ASSOC);
?>

<form action="add_peminjaman.php" method="POST" class="space-y-5" novalidate>

  <div class="grid gap-4 sm:grid-cols-2">

    <!-- Pilih Anggota -->
    <div class="autocomplete">
      <label for="nim" class="field-label">Anggota <span class="text-rose-500">*</span></label>
      <div class="relative">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
        </svg>
        <input autocomplete="off" type="text" id="nim" name="nim" class="input-icon"
               placeholder="Cari Anggota..." data-autofocus required />
      </div>
      <div id="search_results" class="autocomplete-panel"></div> <!-- Menampilkan hasil pencarian -->
      <p class="field-hint">Ketik NIM atau nama anggota.</p>
    </div>

    <!-- Tanggal Pinjam -->
    <div>
      <label for="tgl_pinjam" class="field-label">Tanggal Pinjam <span class="text-rose-500">*</span></label>
      <input type="date" name="tgl_pinjam" id="tgl_pinjam" class="field" required />
    </div>

    <!-- Pilih Petugas -->
    <div>
      <label for="id_petugas" class="field-label">Petugas <span class="text-rose-500">*</span></label>
      <select name="id_petugas" id="id_petugas" class="field-select" required>
        <option value="" disabled selected>Pilih Petugas</option>
        <?php foreach ($petugas as $p): ?>
          <option value="<?= htmlspecialchars((string) $p['id_petugas']); ?>"><?= htmlspecialchars($p['nama_petugas']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Estimasi Pinjam -->
    <div>
      <label for="estimasi_pinjam" class="field-label">Estimasi Pinjam <span class="text-rose-500">*</span></label>
      <input type="date" name="estimasi_pinjam" id="estimasi_pinjam" class="field" required />
    </div>

    <!-- Pilih Buku -->
    <div class="autocomplete">
      <label for="kode_buku" class="field-label">Buku <span class="text-rose-500">*</span></label>
      <div class="relative">
        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
             fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <input autocomplete="off" type="text" id="kode_buku" name="kode_buku" class="input-icon"
               placeholder="Cari Buku..." required />
      </div>
      <div id="search_results_buku" class="autocomplete-panel"></div> <!-- Menampilkan hasil pencarian buku -->
    </div>

    <!-- Kondisi Buku -->
    <div>
      <label for="kondisi_buku_pinjam" class="field-label">Kondisi Buku <span class="text-rose-500">*</span></label>
      <select name="kondisi_buku_pinjam" id="kondisi_buku_pinjam" class="field-select" required>
        <option value="bagus">Bagus</option>
        <option value="rusak">Rusak</option>
      </select>
    </div>
  </div>

  <p class="flex items-start gap-2.5 text-sm text-slate-500">
    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
    </svg>
    <span><span class="font-semibold text-rose-600">*</span> Maksimal peminjaman adalah 7 hari dari tanggal pinjam.</span>
  </p>

  <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
    <button type="reset" class="btn-secondary btn-sm">Reset</button>
    <button type="submit" class="btn-primary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Tambah
    </button>
  </div>
</form>
