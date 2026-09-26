<?php
session_start();  // Memastikan sesi dimulai

require_once '../../Config/koneksi.php';  // Menghubungkan ke file koneksi database

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
    echo "<script>
              alert('Anggota ini sudah meminjam 2 buku. Tidak dapat meminjam lagi.');
              window.location.href = 'peminjaman.php';
            </script>";
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

    echo "<script>
              alert('Peminjaman berhasil ditambahkan!');
              window.location.href = 'peminjaman.php';
            </script>";
    exit;
  } catch (PDOException $e) {
    echo "<script>
              alert('Gagal menambahkan peminjaman: " . addslashes($e->getMessage()) . "');
              window.location.href = 'peminjaman.php';
            </script>";
    exit;
  }
}


// Query untuk mengambil data anggota, buku, dan petugas
$anggota = $conn->query("SELECT nim, nama FROM anggota WHERE status_mhs = 'Aktif'")->fetchAll(PDO::FETCH_ASSOC);
$buku = $conn->query("SELECT kode_buku, judul_buku FROM buku WHERE stok > 0")->fetchAll(PDO::FETCH_ASSOC);
$petugas = $conn->query("SELECT id_petugas, nama_petugas FROM petugas WHERE status = 'Aktif'")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
  <form action="add_peminjaman.php" method="POST">
    <div class="row">
      <div class="col-md-6">
        <!-- Pilih Anggota -->
        <div class="mb-3">
          <label for="nim" class="form-label">Anggota</label>
          <input autocomplete="off" type="text" id="nim" name="nim" class="form-control" placeholder="Cari Anggota..." required>
          <div id="search_results" class="mt-2"></div> <!-- Menampilkan hasil pencarian -->
        </div>
      </div>
      <div class="col-md-6">
        <!-- Tanggal Pinjam -->
        <div class="mb-3">
          <label for="tgl_pinjam" class="form-label">Tanggal Pinjam</label>
          <input type="date" name="tgl_pinjam" id="tgl_pinjam" class="form-control" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <!-- Pilih Petugas -->
        <div class="mb-3">
          <label for="id_petugas" class="form-label">Petugas</label>
          <select name="id_petugas" id="id_petugas" class="form-select" required>
            <option value="">Pilih Petugas</option>
            <?php foreach ($petugas as $p): ?>
              <option value="<?= $p['id_petugas']; ?>"><?= htmlspecialchars($p['nama_petugas']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="col-md-6">
        <!-- Estimasi Pinjam -->
        <div class="mb-3">
          <label for="estimasi_pinjam" class="form-label">Estimasi Pinjam</label>
          <input type="date" name="estimasi_pinjam" id="estimasi_pinjam" class="form-control" required>
        </div>
      </div>
    </div>
    <div class="row">

      <div class="col-md-6">
        <!-- Pilih Buku -->
        <div class="mb-3">
          <label for="kode_buku" class="form-label">Buku</label>
          <input autocomplete="off" type="text" id="kode_buku" name="kode_buku" class="form-control" placeholder="Cari Buku..." required>
          <div id="search_results_buku" class="mt-2"></div> <!-- Menampilkan hasil pencarian buku -->
        </div>
      </div>
      <div class="col-md-6">
        <!-- Kondisi Buku -->
        <div class="mb-3">
          <label for="kondisi_buku_pinjam" class="form-label">Kondisi Buku</label>
          <select name="kondisi_buku_pinjam" id="kondisi_buku_pinjam" class="form-select" required>
            <option value="bagus">Bagus</option>
            <option value="rusak">Rusak</option>
          </select>
        </div>
      </div>
    </div>
    <div>
      <span class="text-danger">* Maksimal peminjaman adalah 7 hari dari tanggal pinjam.</span>
    </div>
    <div class="d-flex justify-content-end mt-4 rounded-3">
      <button type="reset" class="btn btn-danger me-2">Reset</button>
      <button type="submit" class="btn btn-primary">Tambah</button>
    </div>
  </form>
</div>