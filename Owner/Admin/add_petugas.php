<?php
require_once '../../Config/koneksi.php';

// Fungsi untuk mengambil ID petugas terbaru
function getLastPetugasId($conn)
{
  $stmt = $conn->prepare("SELECT MAX(id_petugas) AS last_id FROM petugas");
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  return $result ? $result['last_id'] : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Ambil data dari form
  $id_petugas = getLastPetugasId($conn) + 1;  // ID otomatis
  $nama_petugas = $_POST['nama_petugas'];
  $username = $_POST['username'];
  $password = $_POST['password'];
  $no_telp = $_POST['no_telp'];
  $jenis_kelamin = $_POST['jenis_kelamin'];
  $profil_gambar = ''; // Gambar akan diupload nanti

  try {
    // Proses upload gambar jika ada
    if (isset($_FILES['profil_gambar']) && $_FILES['profil_gambar']['error'] == 0) {
      $target_dir = "../../Assets/uploads/";
      $profil_gambar = basename($_FILES['profil_gambar']['name']);
      move_uploaded_file($_FILES['profil_gambar']['tmp_name'], $target_dir . $profil_gambar);
    }

    // Menyimpan data petugas ke database tanpa status_petugas
    $stmt = $conn->prepare("INSERT INTO petugas (id_petugas, nama_petugas, username, password, no_telp, jenis_kelamin, profil_gambar) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_petugas, $nama_petugas, $username, $password, $no_telp, $jenis_kelamin, $profil_gambar]);


    if ($stmt) {
      echo "<script>
                    alert('Petugas berhasil ditambahkan.');
                    window.location.href = 'admin.php'; // Redirect ke halaman admin
                </script>";
    }
  } catch (PDOException $e) {
    echo "<script>
                alert('Terjadi kesalahan: " . $e->getMessage() . "');
            </script>";
  }
}
?>

<div class="container">
  <form method="POST" action="add_petugas.php" enctype="multipart/form-data">
    <div class="row g-3">
      <div class="col-md-6">
        <label for="nama_petugas" class="form-label">Nama Petugas</label>
        <input type="text" class="form-control" id="nama_petugas" name="nama_petugas" placeholder="Masukkan Nama Lengkap" required>
      </div>
      <div class="col-md-6">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan Username" required>
      </div>
      <div class="col-md-6">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Password min 8 dan ada karakter khusus" required>
      </div>
      <div class="col-md-6">
        <label for="no_telp" class="form-label">No. Telepon</label>
        <input type="text" class="form-control" id="no_telp" name="no_telp" placeholder="Diwali dengan '+62'" required>
      </div>
      <div class="col-md-6">
        <label class="form-label d-block">Jenis Kelamin</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="jenis_kelamin" id="jenis_kelamin_laki" value="Laki-Laki" required>
          <label class="form-check-label" for="jenis_kelamin_laki">Laki-Laki</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="jenis_kelamin" id="jenis_kelamin_perempuan" value="Perempuan" required>
          <label class="form-check-label" for="jenis_kelamin_perempuan">Perempuan</label>
        </div>
      </div>
      <div class="col-md-6">
        <label for="profil_gambar" class="form-label">Foto Profil</label>
        <input type="file" class="form-control" id="profil_gambar" name="profil_gambar">
      </div>
    </div>
    <div class="d-flex justify-content-end mt-4">
      <button type="reset" class="btn btn-danger me-2">Reset</button>
      <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
  </form>
</div>

<script>
  const noTelpInput = document.getElementById('no_telp');
  noTelpInput.addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, ''); // Menghapus karakter selain angka
  });
</script>