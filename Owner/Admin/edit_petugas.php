<?php
require_once '../../Config/koneksi.php';

// Ambil data petugas berdasarkan ID
$id_petugas = isset($_GET['id_petugas']) ? $_GET['id_petugas'] : null;
if (!$id_petugas) {
    echo "<script>
            alert('ID petugas tidak ditemukan.');
            window.location.href = 'admin.php';
        </script>";
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM petugas WHERE id_petugas = ?");
    $stmt->execute([$id_petugas]);
    $petugas = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$petugas) {
        echo "<script>
              alert('Data petugas tidak ditemukan.');
              window.location.href = 'admin.php';
          </script>";
        exit;
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_petugas = $_POST['nama_petugas'];
    $username = $_POST['username'];
    $no_telp = $_POST['no_telp'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $profil_gambar = $petugas['profil_gambar']; // Default gambar sebelumnya

    try {
        // Proses upload gambar jika ada
        if (isset($_FILES['profil_gambar']) && $_FILES['profil_gambar']['error'] == 0) {
            $target_dir = "../../Assets/uploads/";
            $profil_gambar = basename($_FILES['profil_gambar']['name']);
            move_uploaded_file($_FILES['profil_gambar']['tmp_name'], $target_dir . $profil_gambar);
        }

        // Update data petugas ke database
        $stmt = $conn->prepare("UPDATE petugas 
                                SET nama_petugas = ?, username = ?, no_telp = ?, jenis_kelamin = ?, profil_gambar = ? 
                                WHERE id_petugas = ?");
        $stmt->execute([$nama_petugas, $username, $no_telp, $jenis_kelamin, $profil_gambar, $id_petugas]);

        if ($stmt) {
            echo "<script>
                    alert('Petugas berhasil diperbarui.');
                    window.location.href = 'admin.php';
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
    <form method="POST" action="edit_petugas.php?id_petugas=<?= $id_petugas ?>" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nama_petugas" class="form-label">Nama Petugas</label>
                <input type="text" class="form-control" id="nama_petugas" name="nama_petugas" value="<?= htmlspecialchars($petugas['nama_petugas']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($petugas['username']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="no_telp" class="form-label">No. Telepon</label>
                <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?= htmlspecialchars($petugas['no_telp']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Jenis Kelamin</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="jenis_kelamin" id="jenis_kelamin_laki" value="Laki-Laki" <?= $petugas['jenis_kelamin'] === 'Laki-Laki' ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="jenis_kelamin_laki">Laki-Laki</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="jenis_kelamin" id="jenis_kelamin_perempuan" value="Perempuan" <?= $petugas['jenis_kelamin'] === 'Perempuan' ? 'checked' : '' ?> required>
                    <label class="form-check-label" for="jenis_kelamin_perempuan">Perempuan</label>
                </div>
            </div>
            <div class="col-md-6">
                <label for="profil_gambar" class="form-label">Foto Profil</label>
                <input type="file" class="form-control" id="profil_gambar" name="profil_gambar">
                <?php if ($petugas['profil_gambar']): ?>
                    <img src="../../Assets/uploads/<?= $petugas['profil_gambar'] ?>" alt="Profil Gambar" class="mt-2" width="100">
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
    const noTelpInput = document.getElementById('no_telp');
    noTelpInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, ''); // Menghapus karakter selain angka
    });
</script>