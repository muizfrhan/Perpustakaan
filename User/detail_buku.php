<?php
// Include konfigurasi database
require_once '../Config/koneksi.php';

// Mengambil ID buku dari query string
$kode_buku = isset($_GET['kode_buku']) ? $_GET['kode_buku'] : '';

if (!empty($kode_buku)) {
    // Query untuk mengambil data buku berdasarkan kode_buku
    $query = $conn->prepare("SELECT * FROM buku WHERE kode_buku = :kode_buku");
    $query->bindValue(':kode_buku', $kode_buku, PDO::PARAM_STR); // Gunakan PDO::PARAM_STR untuk ID string
    $query->execute();

    // Cek apakah buku ditemukan
    if ($query->rowCount() > 0) {
        // Ambil data buku
        $buku = $query->fetch(PDO::FETCH_ASSOC);
?>
        <div class="container mt-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <!-- Bagian Gambar Cover Buku -->
                        <div class="col-md-4">
                            <img src="../../Assets/uploads/<?= htmlspecialchars($buku['cover']); ?>" alt="Cover Buku" class="img-fluid rounded shadow-lg mb-4">
                            <div class="mb-3">
                                <p><strong class="text-muted">Stok :</strong> <?= htmlspecialchars($buku['stok']); ?></p>
                                <p><strong class="text-muted">Status :</strong> <?= htmlspecialchars($buku['status']); ?></p>
                            </div>
                        </div>

                        <!-- Bagian Detail Buku -->
                        <div class="col-md-8">
                            <h3 class="text-primary mb-4"><?= htmlspecialchars($buku['judul_buku']); ?></h3>

                            <!-- Info Buku -->
                            <div class="mb-3">
                                <p><strong class="text-muted">Kategori :</strong> <?= htmlspecialchars($buku['kategori']); ?></p>
                                <p><strong class="text-muted">Pengarang :</strong> <?= htmlspecialchars($buku['pengarang']); ?></p>
                                <p><strong class="text-muted">Penerbit :</strong> <?= htmlspecialchars($buku['penerbit']); ?></p>
                                <p><strong class="text-muted">Tanggal Terbit:</strong> <?= htmlspecialchars($buku['tanggal_terbit']); ?></p>
                                <p><strong class="text-muted">Jumlah Halaman:</strong> <?= htmlspecialchars($buku['jumlah_halaman']); ?></p>
                                <p><strong class="text-muted">Bahasa :</strong> <?= htmlspecialchars($buku['bahasa']); ?></p>
                            </div>
                        </div>

                        <!-- Deskripsi Buku -->
                        <div class="mt-4">
                            <h5 class="text-secondary">Deskripsi Buku:</h5>
                            <p class="lead"><?= nl2br(htmlspecialchars($buku['deskripsi_buku'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


<?php
    } else {
        echo "<p class='text-danger'>Buku tidak ditemukan.</p>";
    }
} else {
    echo "<p class='text-danger'>ID Buku tidak valid.</p>";
}
?>