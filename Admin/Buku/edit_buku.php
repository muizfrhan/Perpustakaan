<?php
require_once '../../Config/koneksi.php';

// Ambil data buku berdasarkan kode_buku
if (isset($_GET['kode_buku'])) {
    $kode_buku = $_GET['kode_buku'];

    try {
        $stmt = $conn->prepare("SELECT * FROM buku WHERE kode_buku = ?");
        $stmt->execute([$kode_buku]);
        $buku = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$buku) {
            echo "<script>
                    alert('Buku tidak ditemukan.');
                    window.location.href = 'buku.php';
                  </script>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<script>
                alert('Terjadi kesalahan: " . $e->getMessage() . "');
              </script>";
        exit;
    }
}

// Proses update data buku
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_buku = $_POST['kode_buku'];
    $judul_buku = $_POST['judul_buku'];
    $pengarang = $_POST['pengarang'];
    $penerbit = $_POST['penerbit'];
    $tanggal_terbit = $_POST['tanggal_terbit'];
    $bahasa = $_POST['bahasa'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $jumlah_halaman = $_POST['jumlah_halaman'];
    $deskripsi_buku = $_POST['deskripsi_buku'];

    // Handle file upload for cover
    $cover_name = $_FILES['cover']['name'];
    $cover_tmp = $_FILES['cover']['tmp_name'];
    $cover_folder = '../../Assets/uploads/' . $cover_name;

    try {
        // Pindahkan file cover jika ada
        if (!empty($cover_name)) {
            if (!move_uploaded_file($cover_tmp, $cover_folder)) {
                throw new Exception("Gagal mengupload file cover.");
            }
        } else {
            $cover_name = $buku['cover']; // Gunakan cover lama jika tidak diubah
        }

        // Update data buku
        $stmt = $conn->prepare("UPDATE buku SET judul_buku = ?, pengarang = ?, penerbit = ?, tanggal_terbit = ?, bahasa = ?, kategori = ?, jumlah_halaman = ?, stok = ?, deskripsi_buku = ?, cover = ? WHERE kode_buku = ?");
        $stmt->execute([$judul_buku, $pengarang, $penerbit, $tanggal_terbit, $bahasa, $kategori, $stok, $jumlah_halaman, $deskripsi_buku, $cover_name, $kode_buku]);

        if ($stmt->rowCount() > 0) {
            echo "<script>
                    alert('Buku berhasil diperbarui.');
                    window.location.href = 'buku.php';
                  </script>";
        } else {
            echo "<script>
                    alert('Tidak ada perubahan yang dilakukan.');
                    window.location.href = 'buku.php';
                  </script>";
        }
    } catch (PDOException $e) {
        echo "<script>
                alert('Terjadi kesalahan: " . $e->getMessage() . "');
              </script>";
    }
}
?>

<!-- Form Edit Buku -->
<form method="POST" action="update_buku.php" enctype="multipart/form-data">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="kode_buku" class="form-label">Kode Buku</label>
            <input type="text" class="form-control" id="kode_buku" name="kode_buku" value="<?= $buku['kode_buku'] ?>" readonly>
        </div>
        <div class="col-md-6">
            <label for="judul_buku" class="form-label">Judul Buku</label>
            <input type="text" class="form-control" id="judul_buku" name="judul_buku" value="<?= $buku['judul_buku'] ?>" required>
        </div>
        <div class="col-md-6">
            <label for="pengarang" class="form-label">Pengarang</label>
            <input type="text" class="form-control" id="pengarang" name="pengarang" value="<?= $buku['pengarang'] ?>" required>
        </div>
        <div class="col-md-6">
            <label for="penerbit" class="form-label">Penerbit</label>
            <input type="text" class="form-control" id="penerbit" name="penerbit" value="<?= $buku['penerbit'] ?>" required>
        </div>
        <div class="col-md-6">
            <label for="tanggal_terbit" class="form-label">Tanggal Terbit</label>
            <input type="date" class="form-control" id="tanggal_terbit" name="tanggal_terbit" value="<?= $buku['tanggal_terbit'] ?>" required>
        </div>
        <div class="col-md-6">
            <label for="bahasa" class="form-label">Bahasa</label>
            <select class="form-select" id="bahasa" name="bahasa" required>
                <option value="Indonesia" <?= $buku['bahasa'] === 'Indonesia' ? 'selected' : '' ?>>Indonesia</option>
                <option value="Inggris" <?= $buku['bahasa'] === 'Inggris' ? 'selected' : '' ?>>Inggris</option>
                <option value="Jawa" <?= $buku['bahasa'] === 'Jawa' ? 'selected' : '' ?>>Jawa</option>
                <option value="Arab" <?= $buku['bahasa'] === 'Arab' ? 'selected' : '' ?>>Arab</option>
                <option value="Jepang" <?= $buku['bahasa'] === 'Jepang' ? 'selected' : '' ?>>Jepang</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="kategori" class="form-label">Kategori</label>
            <select class="form-select" id="kategori" name="kategori" required>
                <option value="Pemrograman" <?= $buku['kategori'] === 'Pemrograman' ? 'selected' : '' ?>>Pemrograman</option>
                <option value="Jaringan dan Keamanan" <?= $buku['kategori'] === 'Jaringan dan Keamanan' ? 'selected' : '' ?>>Jaringan dan Keamanan</option>
                <option value="Algoritma dan Struktur Data" <?= $buku['kategori'] === 'Algoritma dan Struktur Data' ? 'selected' : '' ?>>Algoritma dan Struktur Data</option>
                <option value="Basis Data" <?= $buku['kategori'] === 'Basis Data' ? 'selected' : '' ?>>Basis Data</option>
                <option value="Kecerdasan Buatan" <?= $buku['kategori'] === 'Kecerdasan Buatan' ? 'selected' : '' ?>>Kecerdasan Buatan</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="jumlah_halaman" class="form-label">Jumlah Halaman</label>
            <input type="number" class="form-control" id="jumlah_halaman" name="jumlah_halaman" value="<?= $buku['jumlah_halaman'] ?>" required>
        </div>
        <div class="col-md-6">
            <label for="stok" class="form-label">Stok</label>
            <input type="number" class="form-control" id="stok" name="stok" value="<?= $buku['stok'] ?>" required>
        </div>
        <div class="col-md-6">
            <label for="cover" class="form-label">Cover Buku</label>
            <input type="file" class="form-control" id="cover" name="cover">
            <p>Cover Saat Ini: <?= $buku['cover'] ? $buku['cover'] : 'Tidak ada' ?></p>
        </div>
        <div class="col-md-12">
            <label for="deskripsi_buku" class="form-label">Deskripsi Buku</label>
            <textarea class="form-control" id="deskripsi_buku" name="deskripsi_buku" rows="5" required><?= $buku['deskripsi_buku'] ?></textarea>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-4 rounded-3">
        <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
    </div>
</form>