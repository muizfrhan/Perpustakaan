<?php
/**
 * Form Edit Peminjaman (fragment AJAX).
 *
 * Dimuat ke dalam #editModalContent oleh peminjaman.php.
 * Nama field TIDAK BERUBAH agar tetap kompatibel dengan query UPDATE di bawah.
 */
session_start(); // Memastikan sesi dimulai

require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php'; // Menghubungkan ke file koneksi database

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


// Proses pengambilan data untuk edit
// Proses pengambilan data untuk edit
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['kode_pinjam'])) {
  $kode_pinjam = $_GET['kode_pinjam'];

  // Ambil data peminjaman berdasarkan ID
  $sql = "
      SELECT peminjaman.kode_pinjam, peminjaman.nim, peminjaman.kode_buku, 
             peminjaman.id_petugas, peminjaman.tgl_pinjam, 
             peminjaman.estimasi_pinjam, peminjaman.kondisi_buku_pinjam
      FROM peminjaman
      WHERE peminjaman.kode_pinjam = :kode_pinjam
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bindValue(':kode_pinjam', $kode_pinjam, PDO::PARAM_STR);  // Sesuaikan dengan tipe data
  $stmt->execute();
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  // Jika data tidak ditemukan
  if (!$data) {
      flash('danger', 'Data peminjaman tidak ditemukan!');
      header('Location: peminjaman.php');
      exit;
  }

  // Query untuk mengambil data anggota, buku, dan petugas
  $anggota = $conn->query("SELECT nim, nama FROM anggota WHERE status_mhs = 'Aktif'")->fetchAll(PDO::FETCH_ASSOC);
  $buku = $conn->query("SELECT kode_buku, judul_buku FROM buku")->fetchAll(PDO::FETCH_ASSOC);
  $petugas = $conn->query("SELECT id_petugas, nama_petugas FROM petugas")->fetchAll(PDO::FETCH_ASSOC);
}


// Proses update data peminjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $kode_pinjam = $_POST['kode_pinjam'];
  $nim = $_POST['nim'];
  $kode_buku = $_POST['kode_buku'];
  $id_petugas = $_POST['id_petugas'];
  $estimasi_pinjam = $_POST['estimasi_pinjam'];
  $kondisi_buku_pinjam = $_POST['kondisi_buku_pinjam'];

  if (empty($nim) || empty($kode_buku) || empty($id_petugas) || empty($estimasi_pinjam) || empty($kondisi_buku_pinjam)) {
      flash('danger', 'Semua bidang wajib diisi!');
      header('Location: peminjaman.php');
      exit;
  }

  $sql = "
      UPDATE peminjaman
      SET nim = :nim, 
          kode_buku = :kode_buku, 
          id_petugas = :id_petugas, 
          estimasi_pinjam = :estimasi_pinjam, 
          kondisi_buku_pinjam = :kondisi_buku_pinjam
      WHERE kode_pinjam = :kode_pinjam
  ";

  $stmt = $conn->prepare($sql);
  $stmt->bindValue(':nim', $nim);
  $stmt->bindValue(':kode_buku', $kode_buku);
  $stmt->bindValue(':id_petugas', $id_petugas);
  $stmt->bindValue(':estimasi_pinjam', $estimasi_pinjam);
  $stmt->bindValue(':kondisi_buku_pinjam', $kondisi_buku_pinjam);
  $stmt->bindValue(':kode_pinjam', $kode_pinjam, PDO::PARAM_STR);

  try {
      if ($stmt->execute()) {
          flash('success', 'Data peminjaman berhasil diperbarui!');
      } else {
          flash('danger', 'Gagal memperbarui data peminjaman. Silakan coba lagi.');
      }
  } catch (PDOException $e) {
      flash('danger', 'Gagal memperbarui data peminjaman: ' . $e->getMessage());
  }

  header('Location: peminjaman.php');
  exit;
}

$e = static fn($v) => htmlspecialchars((string) $v);
?>

<form action="edit_peminjaman.php" method="POST" class="space-y-5" novalidate>
  <input type="hidden" name="kode_pinjam" value="<?= $e($data['kode_pinjam']); ?>" />

  <div class="grid gap-4 sm:grid-cols-2">

    <div>
      <label for="nim" class="field-label">Anggota <span class="text-rose-500">*</span></label>
      <select name="nim" id="nim" class="field-select" data-autofocus required>
        <option value="" disabled selected>Pilih Anggota</option>
        <?php foreach ($anggota as $a): ?>
          <option value="<?= $e($a['nim']); ?>" <?= $a['nim'] === $data['nim'] ? 'selected' : ''; ?>>
            <?= $e($a['nim']); ?> &ndash; <?= $e($a['nama']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="kode_buku" class="field-label">Buku <span class="text-rose-500">*</span></label>
      <select name="kode_buku" id="kode_buku" class="field-select" required>
        <option value="" disabled selected>Pilih Buku</option>
        <?php foreach ($buku as $b): ?>
          <option value="<?= $e($b['kode_buku']); ?>" <?= $b['kode_buku'] === $data['kode_buku'] ? 'selected' : ''; ?>>
            <?= $e($b['kode_buku']); ?> &ndash; <?= $e($b['judul_buku']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="id_petugas" class="field-label">Petugas <span class="text-rose-500">*</span></label>
      <select name="id_petugas" id="id_petugas" class="field-select" required>
        <option value="" disabled selected>Pilih Petugas</option>
        <?php foreach ($petugas as $p): ?>
          <option value="<?= $e($p['id_petugas']); ?>" <?= $p['id_petugas'] === $data['id_petugas'] ? 'selected' : ''; ?>>
            <?= $e($p['nama_petugas']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="estimasi_pinjam" class="field-label">Estimasi Pinjam <span class="text-rose-500">*</span></label>
      <input type="datetime-local" name="estimasi_pinjam" id="estimasi_pinjam" class="field"
             value="<?= date('Y-m-d\TH:i', strtotime($data['estimasi_pinjam'])); ?>" required />
    </div>

    <div>
      <label for="kondisi_buku_pinjam" class="field-label">Kondisi Buku <span class="text-rose-500">*</span></label>
      <select name="kondisi_buku_pinjam" id="kondisi_buku_pinjam" class="field-select" required>
        <option value="bagus" <?= $data['kondisi_buku_pinjam'] === 'bagus' ? 'selected' : ''; ?>>Bagus</option>
        <option value="rusak" <?= $data['kondisi_buku_pinjam'] === 'rusak' ? 'selected' : ''; ?>>Rusak</option>
      </select>
    </div>

    <div>
      <span class="field-label">Tanggal Pinjam</span>
      <p class="field mt-0 cursor-not-allowed bg-slate-50 text-slate-500">
        <?= date('d/m/Y H:i', strtotime($data['tgl_pinjam'])) ?>
      </p>
      <p class="field-hint">Tanggal pinjam tidak dapat diubah dari form ini.</p>
    </div>
  </div>

  <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
    <button type="reset" class="btn-secondary btn-sm">Reset</button>
    <button type="submit" class="btn-warning btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Simpan Perubahan
    </button>
  </div>
</form>
