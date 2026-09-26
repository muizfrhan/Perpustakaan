<?php
require_once '../Config/koneksi.php'; // Sesuaikan path dengan struktur folder
include 'header.php'; // Sesuaikan path dengan struktur folder

// Query untuk mengambil data buku yang paling banyak dipinjam
$query = $conn->query("
SELECT 
    b.kode_buku, 
    b.judul_buku, 
    b.cover, 
    b.penerbit, 
    COUNT(p.kode_buku) AS jumlah_peminjaman
FROM 
    buku b
LEFT JOIN 
    peminjaman p ON b.kode_buku = p.kode_buku
GROUP BY 
    b.kode_buku
ORDER BY 
    jumlah_peminjaman DESC
LIMIT 6;
");
$buku = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Content -->
<section class="search-bar d-flex mt-3">
    <input type="text" class="form-control" placeholder="Search" />
    <button class="btn"><i class="fas fa-search"></i></button>
</section>
<section class="banner mt-2 d-flex justify-content-between align-items-center">
    <h2 class="fw-bold fs-7">Minimal Literasi lah dek!</h2>
    <img src="../Assets/img/pngegg.png" alt="Books" height="200" />
</section>

<!-- Konten Buku Populer -->
<section class="conten mt-2">
    <h3 class="mb-4">Buku Populer</h3>
    <div class="row g-3">
        <?php foreach ($buku as $b): ?>
            <div class="col-6 col-md-4">
                <div class="card">
                    <!-- Cover Buku -->
                    <img src="../Assets/uploads/<?= $b['cover'] ?>" class="img-fluid rounded-3" alt="<?= $b['judul_buku'] ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <!-- Judul Buku -->
                        <h5 class="card-title" style="font-size: 1rem;"><?= $b['judul_buku'] ?></h5>
                        <!-- Deskripsi Buku -->
                        <p class="card-text" style="font-size: 0.9rem;"><?= $b['penerbit'] ?></p>
                        <!-- Tombol Detail -->
                        <button class="btn btn-primary btn-sm rounded-2 text-white" data-bs-toggle="modal" data-bs-target="#detailModal" onclick="loadDetailForm('<?= $b['kode_buku']; ?>')">
                            Lihat Detail
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Modal untuk Detail Buku -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Detail buku akan diisi di sini -->
                <div id="modalContent">
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript untuk Mengisi Modal -->
<script>
    function loadDetailForm(kodeBuku) {
        // Mengambil data buku dari server menggunakan AJAX
        fetch(`get_detail_buku.php?kode_buku=${kodeBuku}`)
            .then(response => response.json())
            .then(data => {
                // Mengisi modal dengan data buku
                const modalContent = document.getElementById('modalContent');
                modalContent.innerHTML = `
     <div class="row">
    <!-- Kolom Gambar Cover -->
    <div class="col-6">
        <div class="card shadow-sm border-0">
            <img src="../Assets/uploads/${data.cover}" class="img-fluid rounded-start" alt="${data.judul_buku}" object-fit: cover; max-width: 100%;">
        </div>
    </div>

    <!-- Kolom Detail Buku (Samping Gambar di Mobile) -->
    <div class="col-5">
        <h3 class="fw-bold text-primary fs-6 mb-1">${data.judul_buku}</h3>
    </div>
</div>
<!-- Detail Buku Bagian 1 -->
<div class="row mt-3">
<hr class="my-2">
    <div class="col-md-3 col-6">
        <p class="mb-3"><strong class="text-secondary fs-6">Pengarang:</strong> <span class="text-dark small">${data.pengarang}</span></p>
        <p class="mb-3"><strong class="text-secondary fs-6">Penerbit:</strong> <span class="text-dark small">${data.penerbit}</span></p>
        <p class="mb-3"><strong class="text-secondary fs-6">Tanggal Terbit:</strong> <span class="text-dark small">${data.tanggal_terbit}</span></p>
    </div>

    <!-- Detail Buku Bagian 2 -->
    <div class="col-md-6 col-6">
        <p class="mb-3"><strong class="text-secondary fs-6">Jumlah Halaman:</strong> <span class="text-dark small">${data.jumlah_halaman}</span></p>
        <p class="mb-3"><strong class="text-secondary fs-6">Bahasa:</strong> <span class="text-dark small">${data.bahasa}</span></p>
        <p class="mb-3"><strong class="text-secondary fs-6">Stok:</strong> <span class="text-dark small">${data.stok}</span></p>
        <p class="mb-3"><strong class="text-secondary fs-6">Status:</strong> <span class="badge ${data.status === 'Tersedia' ? 'bg-success' : 'bg-danger'}">${data.status}</span></p>
    </div>
</div>
<!-- Deskripsi Buku -->
<div class="row mt-3">
    <div class="col-12">
        <hr class="my-2">
        <h5 class="fw-bold text-secondary fs-6 mb-2">Deskripsi Buku</h5>
        <p class="text-dark small">${data.deskripsi_buku}</p>
    </div>
</div>

            `;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>