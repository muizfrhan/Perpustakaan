<?php
session_start(); // Memastikan sesi dimulai

require_once '../../Config/koneksi.php'; // Menghubungkan ke file koneksi database

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
      echo '<p class="text-danger">Data peminjaman tidak ditemukan!</p>';
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
      echo '<script>alert("Semua bidang wajib diisi!");</script>';
  } else {
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

      if ($stmt->execute()) {
          echo '<script>alert("Data peminjaman berhasil diperbarui!"); window.location.href = "peminjaman.php";</script>';
      } else {
          echo '<script>alert("Gagal memperbarui data peminjaman. Silakan coba lagi.");</script>';
      }
  }
}

?>

<div class="container">
  <form action="edit_peminjaman.php" method="POST">
    <input type="hidden" name="kode_pinjam" value="<?= $data['kode_pinjam']; ?>">
    
    <div class="row">
      <div class="col-md-6">
        <div class="mb-3">
          <label for="nim" class="form-label">Anggota</label>
          <select name="nim" id="nim" class="form-select" required>
            <option value="">Pilih Anggota</option>
            <?php foreach ($anggota as $a): ?>
              <option value="<?= $a['nim']; ?>" <?= $a['nim'] === $data['nim'] ? 'selected' : ''; ?>>
                <?= $a['nim']; ?> - <?= $a['nama']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="col-md-6">
        <div class="mb-3">
          <label for="kode_buku" class="form-label">Buku</label>
          <select name="kode_buku" id="kode_buku" class="form-select" required>
            <option value="">Pilih Buku</option>
            <?php foreach ($buku as $b): ?>
              <option value="<?= $b['kode_buku']; ?>" <?= $b['kode_buku'] === $data['kode_buku'] ? 'selected' : ''; ?>>
                <?= $b['kode_buku']; ?> - <?= $b['judul_buku']; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6">
        <div class="mb-3">
        <label for="id_petugas" class="form-label">Petugas</label>
      <select name="id_petugas" id="id_petugas" class="form-select" required>
        <option value="">Pilih Petugas</option>
        <?php foreach ($petugas as $p): ?>
          <option value="<?= $p['id_petugas']; ?>" <?= $p['id_petugas'] === $data['id_petugas'] ? 'selected' : ''; ?>>
            <?= $p['nama_petugas']; ?>
          </option>
        <?php endforeach; ?>
      </select>
        </div>
      </div>
      <div class="col-md-6">
        <div class="mb-3">
          <label for="estimasi_pinjam" class="form-label">Estimasi Pinjam</label>
          <input type="datetime-local" name="estimasi_pinjam" id="estimasi_pinjam" 
                 class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($data['estimasi_pinjam'])); ?>" required>
        </div>
      </div>
    </div>
    
    <div class="row">
        <div class="mb-3">
          <label for="kondisi_buku_pinjam" class="form-label">Kondisi Buku</label>
          <select name="kondisi_buku_pinjam" id="kondisi_buku_pinjam" class="form-select" required>
            <option value="bagus" <?= $data['kondisi_buku_pinjam'] === 'bagus' ? 'selected' : ''; ?>>Bagus</option>
            <option value="rusak" <?= $data['kondisi_buku_pinjam'] === 'rusak' ? 'selected' : ''; ?>>Rusak</option>
          </select>
        </div>
    </div>
    
    <div class="d-flex justify-content-end mt-4">
      <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
    </div>
  </form>
</div>
