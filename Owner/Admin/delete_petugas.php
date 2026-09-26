<?php
require_once '../../Config/koneksi.php';

if (isset($_GET['id_petugas'])) {
  $idPetugas = $_GET['id_petugas'];

  // Update status petugas menjadi "Tidak Aktif"
  $query = $conn->prepare("UPDATE petugas SET status = 'Tidak Aktif' WHERE id_petugas = :id_petugas");
  $query->bindParam(':id_petugas', $idPetugas, PDO::PARAM_INT);

  if ($query->execute()) {
    echo "<script>
            alert('Admin Berhasil menonaktifkan!');
            window.location.href = 'admin.php';
          </script>";
  } else {
    echo "<script>
            alert('Gagal menonaktifkan!');
            window.location.href = 'admin.php';
          </script>";
  }
} else {
  echo "<script>
          alert('ID petugas tidak ditemukan!');
          window.location.href = 'admin.php';
        </script>";
}
