<?php
require_once '../../Config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_buku = $_POST['kode_buku'];
    $judul_buku = $_POST['judul_buku'];
    $pengarang = $_POST['pengarang'];
    $penerbit = $_POST['penerbit'];
    $tanggal_terbit = $_POST['tanggal_terbit'];
    $bahasa = $_POST['bahasa'];
    $stok = $_POST['stok'];
    $kategori = $_POST['kategori'];
    $jumlah_halaman = $_POST['jumlah_halaman'];
    $deskripsi_buku = $_POST['deskripsi_buku'];

    // Handle file upload for cover
    $cover_name = $_FILES['cover']['name'];
    $cover_tmp = $_FILES['cover']['tmp_name'];
    $cover_folder = '../../Assets/img/uploads' . $cover_name;

    try {
        // Cek apakah kode_buku sudah ada
        $stmt = $conn->prepare("SELECT COUNT(*) FROM buku WHERE kode_buku = ?");
        $stmt->execute([$kode_buku]);
        $kode_buku_exists = $stmt->fetchColumn();

        if ($kode_buku_exists > 0) {
            echo "<script>
                    alert('Kode Buku sudah terdaftar. Harap masukkan Kode Buku yang berbeda.');
                    window.history.back(); // Kembali ke form
                  </script>";
            exit;
        }

        // Pindahkan file cover ke folder uploads jika ada
        if (!empty($cover_name)) {
            if (!move_uploaded_file($cover_tmp, $cover_folder)) {
                throw new Exception("Gagal mengupload file cover.");
            }
        } else {
            $cover_name = null; // Jika tidak ada file yang diunggah
        }

        // Jika kode_buku belum ada, lanjutkan proses simpan
        $stmt = $conn->prepare("INSERT INTO buku (kode_buku, judul_buku, pengarang, penerbit, tanggal_terbit, bahasa, stok, kategori, jumlah_halaman, deskripsi_buku, cover) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kode_buku, $judul_buku, $pengarang, $penerbit, $tanggal_terbit, $bahasa, $stok, $kategori, $jumlah_halaman, $deskripsi_buku, $cover_name]);

        if ($stmt) {
            echo "<script>
                    alert('Buku berhasil ditambahkan.');
                    window.location.href = 'buku.php'; // Redirect ke halaman buku
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
    <form method="POST" action="add_buku.php" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="kode_buku" class="form-label">Kode Buku</label>
                <input type="text" class="form-control" placeholder="Sesuai dibelakang buku" id="kode_buku" name="kode_buku" required>
                <div class="invalid-feedback">Harap masukkan kode buku.</div>
            </div>
            <div class="col-md-6">
                <label for="judul_buku" class="form-label">Judul Buku</label>
                <input type="text" class="form-control" id="judul_buku" name="judul_buku" placeholder="Masukkan judul lengkap buku">
                <div class="invalid-feedback">Harap masukkan judul buku.</div>
            </div>
            <div class="col-md-6">
                <label for="pengarang" class="form-label">Pengarang</label>
                <input type="text" class="form-control" id="pengarang" name="pengarang" placeholder="Masukkan nama Pengarang buku" required>
                <div class="invalid-feedback">Harap masukkan nama pengarang.</div>
            </div>
            <div class="col-md-6">
                <label for="penerbit" class="form-label">Penerbit</label>
                <input type="text" class="form-control" id="penerbit" name="penerbit" placeholder="Masukkan nama penerbit buku" required>
                <div class="invalid-feedback">Harap masukkan nama penerbit.</div>
            </div>
            <div class="col-md-6">
                <label for="tanggal_terbit" class="form-label">Tanggal Terbit</label>
                <input type="date" class="form-control" id="tanggal_terbit" name="tanggal_terbit" required>
                <div class="invalid-feedback">Harap masukkan tanggal terbit.</div>
            </div>
            <div class="col-md-6">
                <label for="bahasa" class="form-label">Bahasa</label>
                <select class="form-select" id="bahasa" name="bahasa" required>
                    <option value="" disabled selected>Pilih bahasa buku</option>
                    <option value="Indonesia">Indonesia</option>
                    <option value="Inggris">Inggris</option>
                    <option value="Jawa">Jawa</option>
                    <option value="Arab">Arab</option>
                    <option value="Jepang">Jepang</option>
                </select>
                <div class="invalid-feedback">Harap pilih bahasa buku.</div>
            </div>

            <div class="col-md-6">
                <label for="kategori" class="form-label">Kategori</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="" disabled selected>Pilih kategori buku</option>
                    <option value="Pemrograman">Pemrograman</option>
                    <option value="Jaringan dan Keamanan">Jaringan dan Keamanan</option>
                    <option value="Algoritma dan Struktur Data">Algoritma dan Struktur Data</option>
                    <option value="Basis Data">Basis Data</option>
                    <option value="Kecerdasan Buatan">Kecerdasan Buatan</option>
                </select>
                <div class="invalid-feedback">Harap pilih kategori buku.</div>
            </div>

            <div class="col-md-6">
                <label for="stok" class="form-label">Stok</label>
                <input type="number" class="form-control" id="stok" name="stok" required>
                <div class="invalid-feedback">Harap masukkan jumlah halaman.</div>
            </div>
            <div class="col-md-6">
                <label for="jumlah_halaman" class="form-label">Jumlah Halaman</label>
                <input type="number" class="form-control" id="jumlah_halaman" name="jumlah_halaman" required>
                <div class="invalid-feedback">Harap masukkan jumlah halaman.</div>
            </div>
            <div class="col-md-6">
                <label for="cover" class="form-label">Cover Buku</label>
                <input type="file" class="form-control" id="cover" name="cover">
                <div class="invalid-feedback">Harap upload file cover.</div>
            </div>
            <div class="col-md-12">
                <label for="deskripsi_buku" class="form-label">Deskripsi Buku</label>
                <textarea class="form-control" id="deskripsi_buku" name="deskripsi_buku" rows="5" placeholder="Masukkan deskripsi singkat tentang buku" required></textarea>
                <div class="invalid-feedback">Harap masukkan deskripsi buku.</div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4 rounded-3">
            <button type="reset" class="btn btn-danger me-2">Reset</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>