<?php
require_once '../../Config/koneksi.php';

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
            // Ambil cover lama jika tidak ada perubahan
            $stmt = $conn->prepare("SELECT cover FROM buku WHERE kode_buku = ?");
            $stmt->execute([$kode_buku]);
            $buku = $stmt->fetch(PDO::FETCH_ASSOC);
            $cover_name = $buku['cover'];
        }

        // Update data buku
        $stmt = $conn->prepare("
            UPDATE buku 
            SET judul_buku = ?, pengarang = ?, penerbit = ?, tanggal_terbit = ?, bahasa = ?, kategori = ?, stok = ?, jumlah_halaman = ?, deskripsi_buku = ?, cover = ? 
            WHERE kode_buku = ?
        ");
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
    } catch (Exception $e) {
        echo "<script>
                alert('Terjadi kesalahan: " . $e->getMessage() . "');
              </script>";
    }
} else {
    echo "<script>
            alert('Metode request tidak valid.');
            window.location.href = 'buku.php';
          </script>";
}
