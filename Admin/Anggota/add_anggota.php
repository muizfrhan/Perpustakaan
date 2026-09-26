<?php
require_once '../../Config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    $nama = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $kelas = $_POST['kelas'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $jurusan = $_POST['jurusan'];
    $no_telp = $_POST['no_telp'];
    $status_mhs = 'Aktif'; // Default status mahasiswa

    try {
        // Cek apakah NIM sudah ada
        $stmt = $conn->prepare("SELECT COUNT(*) FROM anggota WHERE nim = ?");
        $stmt->execute([$nim]);
        $nim_exists = $stmt->fetchColumn();

        if ($nim_exists > 0) {
            echo "<script>
                    alert('NIM sudah terdaftar. Harap masukkan NIM yang berbeda.');
                    window.history.back(); // Kembali ke form
                </script>";
            exit;
        }

        // Simpan password langsung tanpa hashing (plaintext)
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Dihapus karena kita tidak ingin hashing

        // Jika NIM belum ada, lanjutkan proses simpan
        $stmt = $conn->prepare("INSERT INTO anggota (nim, nama, jenis_kelamin, kelas, tgl_lahir, jurusan, no_telp, status_mhs) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nim, $nama, $jenis_kelamin, $kelas, $tgl_lahir, $jurusan, $no_telp, $status_mhs]); // Simpan password asli (plaintext)

        if ($stmt) {
            echo "<script>
                    alert('Anggota berhasil ditambahkan.');
                    window.location.href = 'anggota.php'; // Redirect ke halaman anggota
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
    <form method="POST" action="add_anggota.php">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nim" class="form-label">NIM</label>
                <input type="number" class="form-control" id="nim" name="nim" placeholder="Contoh: 230103161" required pattern="\d+" title="Hanya boleh angka">
            </div>
            <div class="col-md-6">
                <label for="jurusan" class="form-label">Jurusan</label>
                <select class="form-select" id="jurusan" name="jurusan" required>
                    <option value="" disabled selected>Pilih Jurusan Anda</option>
                    <option value="D4 Teknologi Rekayasa Perangkat Lunak">D4 Teknologi Rekayasa Perangkat Lunak</option>
                    <option value="S1 Teknik Informatika">S1 Teknik Informatika</option>
                    <option value="S1 Sistem Informasi">S1 Sistem Informasi</option>
                    <option value="D3 Teknik Komputer">D3 Teknik Komputer</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="nama" class="form-label">Nama</label>
                <input type="text" class="form-control" id="nama" name="nama" placeholder="masukkan nama lengkap" required>
            </div>
            <div class="col-md-6">
                <label for="kelas" class="form-label">Kelas</label>
                <select class="form-select" id="kelas" name="kelas" required>
                    <option value="" disabled selected>Pilih Kelas</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="no_telp" class="form-label">No. Telpon</label>
                <input type="text" class="form-control" id="no_telp" name="no_telp" placeholder="diawala '+62' dan wa aktif" required>
            </div>

            <div class="col-md-6">
                <label for="tgl_lahir" class="form-label">Tanggal Lahir</label>
                <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" required>
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

        </div>
        <div class="d-flex justify-content-end mt-4 rounded-3">
            <button type="reset" class="btn btn-danger me-2">Reset</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>

</div>

<script>
    const nimInput = document.getElementById('nim');
    nimInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, ''); // Menghapus karakter selain angka
    });
</script>