<?php
require_once '../../Config/koneksi.php';

$nim = $_GET['nim'] ?? '';

// Query untuk mendapatkan data anggota berdasarkan NIM
$stmt = $conn->prepare("SELECT * FROM Anggota WHERE nim = ?");
$stmt->execute([$nim]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC) ?? [
  'nama' => '',
  'jenis_kelamin' => '',
  'kelas' => '',
  'tgl_lahir' => '',
  'jurusan' => '',
  'status_mhs' => '',
  'no_telp' => ''
];
?>

<form method="POST" action="update_anggota.php">
  <div class="row g-3">
    <div class="col-md-6">
      <label for="nama" class="form-label">Nama</label>
      <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($anggota['nama']) ?>" required>
    </div>
    <div class="col-md-6">
      <label for="jurusan" class="form-label">Jurusan</label>
      <select class="form-select" id="jurusan" name="jurusan" required>
        <option value="" disabled>Pilih Jurusan Anda</option>
        <option value="D4 Teknologi Rekayasa Perangkat Lunak" <?= $anggota['jurusan'] === 'D4 Teknologi Rekayasa Perangkat Lunak' ? 'selected' : '' ?>>D4 Teknologi Rekayasa Perangkat Lunak</option>
        <option value="S1 Teknik Informatika" <?= $anggota['jurusan'] === 'S1 Teknik Informatika' ? 'selected' : '' ?>>S1 Teknik Informatika</option>
        <option value="S1 Sistem Informasi" <?= $anggota['jurusan'] === 'S1 Sistem Informasi' ? 'selected' : '' ?>>S1 Sistem Informasi</option>
        <option value="D3 Teknik Komputer" <?= $anggota['jurusan'] === 'D3 Teknik Komputer' ? 'selected' : '' ?>>D3 Teknik Komputer</option>
      </select>
    </div>
    <div class="col-md-6">
      <label for="kelas" class="form-label">Kelas</label>
      <input type="text" class="form-control" id="kelas" name="kelas" value="<?= htmlspecialchars($anggota['kelas']) ?>" required>
    </div>
    <div class="col-md-6">
      <label for="tgl_lahir" class="form-label">Tanggal Lahir</label>
      <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" value="<?= htmlspecialchars($anggota['tgl_lahir']) ?>" required>
    </div>
    <div class="col-md-6">
      <label for="no_telp" class="form-label">No. Telpon</label>
      <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?= htmlspecialchars($anggota['no_telp']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label d-block">Jenis Kelamin</label>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="jenis_kelamin" id="jenis_kelamin_laki" value="Laki-Laki" <?= $anggota['jenis_kelamin'] === 'Laki-Laki' ? 'checked' : '' ?> required>
        <label class="form-check-label" for="jenis_kelamin_laki">Laki-Laki</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="jenis_kelamin" id="jenis_kelamin_perempuan" value="Perempuan" <?= $anggota['jenis_kelamin'] === 'Perempuan' ? 'checked' : '' ?> required>
        <label class="form-check-label" for="jenis_kelamin_perempuan">Perempuan</label>
      </div>
    </div>
    <div class="col-md-6">
      <label for="status_mhs" class="form-label">Status Mahasiswa</label>
      <select class="form-select" id="status_mhs" name="status_mhs" required>
        <option value="" disabled>Pilih Status Mahasiswa</option>
        <option value="Aktif" <?= $anggota['status_mhs'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
        <option value="Tidak Aktif" <?= $anggota['status_mhs'] === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
      </select>
    </div>

  </div>
  <div class="d-flex justify-content-end mt-4 rounded-3">
    <button type="reset" class="btn btn-danger me-2">Reset</button>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
  </div>
  <input type="hidden" name="nim" value="<?= htmlspecialchars($nim); ?>">
</form>