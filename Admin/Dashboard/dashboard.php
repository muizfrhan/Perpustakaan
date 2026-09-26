<?php
session_start(); // Aktifkan sesi
require_once '../../Config/koneksi.php';
include '../Layouts/header.php';



// Query untuk mendapatkan jumlah anggota, buku, peminjaman, dan pengembalian
$anggotaResult = $conn->query("SELECT * FROM anggota");
$bukuResult = $conn->query("SELECT * FROM buku");
$peminjamanResult = $conn->query("SELECT * FROM peminjaman");
$pengembalianResult = $conn->query("SELECT * FROM pengembalian");

// Pastikan ada sesi untuk ID petugas
if (!isset($_SESSION['id_petugas'])) {
    header("Location: ../Layouts/login.php");
    exit();
}

$id_petugas = $_SESSION['id_petugas']; // Ambil ID petugas dari sesi

// Query untuk mendapatkan nama petugas
$query = "SELECT nama_petugas, profil_gambar FROM petugas WHERE id_petugas = :id_petugas";
$stmt = $conn->prepare(query: $query);
$stmt->bindParam(':id_petugas', $id_petugas, PDO::PARAM_INT);
$stmt->execute();

$nama_petugas = "Tidak Diketahui"; // Default jika tidak ditemukan

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nama_petugas = htmlspecialchars($row['nama_petugas']);
    $profil_gambar = htmlspecialchars($row['profil_gambar']);
}

// Tanggal otomatis sesuai login
$tanggal_hari_ini = date('jS F Y'); // Format: 14th Aug 2023

// Query untuk peminjaman yang perlu dikembalikan
$queryPinjamKembali = "
    SELECT p.kode_pinjam, a.nama, p.kode_buku, p.estimasi_pinjam
    FROM peminjaman p
    JOIN anggota a ON p.nim = a.nim
    WHERE p.estimasi_pinjam < CURDATE() AND p.status = 'Dipinjam'
";
$stmtPinjamKembali = $conn->query($queryPinjamKembali);

// Query untuk pengembalian dengan status belum lunas
$queryBelumLunas = "
    SELECT pk.kode_kembali, a.nama, pk.denda, pk.pembayaran
    FROM pengembalian pk
    JOIN peminjaman p ON pk.kode_pinjam = p.kode_pinjam
    JOIN anggota a ON p.nim = a.nim
    WHERE pk.status = 'Belum Lunas'
";
$stmtBelumLunas = $conn->query($queryBelumLunas);
?>
<section class="home-section">
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- Judul dan Tanggal -->
            <div>
                <h2 class="fw-bold text-dark mb-0">Dashboard</h2>
                <p class="text-muted"><?= $tanggal_hari_ini; ?></p>
            </div>

            <!-- Bagian Profil -->
            <div class="d-flex align-items-center gap-4">
                <!-- Ikon Pesan -->
                <button class="btn btn-light border rounded-4 message-icon">
                    <i class="fas fa-sign-out-alt rotated-icon" id="log_out" onclick="confirmLogout()"></i>
                </button>
                <div class="d-flex align-items-center">
                    <img src="../../Assets/uploads/<?= htmlspecialchars($profil_gambar); ?>" alt="Profile Image" class="profile-img me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                    <div>
                        <h6 class="fw-bold mb-0"><?= $nama_petugas; ?></h6>
                        <p class="mb-0 text-muted">Admin</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Anggota Terdaftar -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0 mb-4 anggota-card">
                    <div class="card-body text-center anggota-content mt-4 mb-4 me-4">
                        <div class="icon-box d-flex align-items-center justify-content-center">
                            <div class="icon-circle mb-3">
                                <i class="bx bx-user fs-2"></i>
                            </div>
                            <div class="ms-4 text-start">
                                <h3 class="fw-bold mb-1"><?= $anggotaResult->rowCount(); ?></h3>
                                <p>Anggota</p>
                            </div>
                        </div>
                        <a href="../Anggota/anggota.php" class="btn btn-light btn-sm rounded-3">Lihat Semua</a>
                    </div>
                </div>
            </div>
            <!-- Buku Tersedia -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0 mb-4 buku-card">
                    <div class="card-body text-center buku-content mt-4 mb-4 me-4">
                        <div class="icon-box d-flex align-items-center justify-content-center">
                            <div class="icon-circle mb-3">
                                <i class="bx bx-book fs-2"></i>
                            </div>
                            <div class="ms-4 text-start">
                                <h3 class="fw-bold mb-1"><?= $bukuResult->rowCount(); ?></h3>
                                <p>Buku</p>
                            </div>
                        </div>
                        <a href="../Buku/buku.php" class="btn btn-light btn-sm rounded-3">Lihat Semua</a>
                    </div>
                </div>
            </div>
            <!-- Peminjaman -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0 mb-4 peminjaman-card">
                    <div class="card-body text-center peminjaman-content mt-4 mb-4 me-4">
                        <div class="icon-box d-flex align-items-center justify-content-center">
                            <div class="icon-circle mb-3">
                                <i class="bx bx-book-reader fs-2"></i>
                            </div>
                            <div class="ms-4 text-start">
                                <h3 class="fw-bold mb-1"><?= $peminjamanResult->rowCount(); ?></h3>
                                <p>Peminjaman</p>
                            </div>
                        </div>
                        <a href="../Peminjaman/peminjaman.php" class="btn btn-light btn-sm rounded-3">Lihat Semua</a>
                    </div>
                </div>
            </div>
            <!-- Pengembalian -->
            <div class="col-md-3">
                <div class="card shadow-sm border-0 mb-4 pengembalian-card">
                    <div class="card-body text-center pengembalian-content mt-4 mb-4 me-4">
                        <div class="icon-box d-flex align-items-center justify-content-center">
                            <div class="icon-circle mb-3">
                                <i class="bx bx-reset fs-2"></i>
                            </div>
                            <div class="ms-4 text-start">
                                <h3 class="fw-bold mb-1"><?= $pengembalianResult->rowCount(); ?></h3>
                                <p>Pengembalian</p>
                            </div>
                        </div>
                        <a href="../Pengembalian/pengembalian.php" class="btn btn-light btn-sm rounded-3">Lihat Semua</a>
                    </div>
                </div>
            </div>
        </div>


        <!-- Flex Container for Calendar and Lists -->
        <div class="row">
            <div class="col-md-6">
                <div class="calendar border border-secondary border-opacity-75 p-3 rounded-3 d-flex">
                    <!-- Calendar Section -->
                    <div class="calendar-content flex-grow-1">
                        <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                            <button id="prev" class="btn btn-primary">❮</button>
                            <h2 id="month-year" class="mb-0">January 2025</h2>
                            <button id="next" class="btn btn-primary">❯</button>
                        </div>
                        <div class="calendar-grid" id="calendar-grid" class="d-grid grid-template-columns-7 gap-2 text-center">
                            <!-- Grid of days will go here -->
                        </div>
                    </div>
                    <div class="anime-image ms-3" style="width: 50%; padding: px;">
                        <img id="random-image" src="https://via.placeholder.com/150" alt="Mirrored Image" class="img-fluid rounded-3" style="transform: scaleX(-1);" />
                    </div>
                </div>
            </div>
            <!-- Daftar Pengembalian yang Perlu Dikembalikan -->
            <div class="col-md-6">
                <!-- Pengembalian yang Perlu Dikembalikan -->
                <div class="d-flex card shadow-sm p-3 border-0 rounded-4" style="background-color: #f0f9ff;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box rounded-circle text-center" style="background-color: #d1f2ff; width: 50px; height: 50px;">
                            <i class="bx bx-time-five text-primary" style="font-size: 24px; line-height: 50px;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Perlu Dikembalikan</h5>
                            <p class="text-muted small">Daftar buku yang harus segera dikembalikan</p>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <?php if ($stmtPinjamKembali->rowCount() > 0): ?>
                            <?php while ($row = $stmtPinjamKembali->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Kode: <?= htmlspecialchars($row['kode_pinjam']); ?></h6>
                                        <p class="text-muted mb-0 small">Nama: <?= htmlspecialchars($row['nama']); ?></p>
                                        <p class="text-muted small">Estimasi: <?= htmlspecialchars($row['estimasi_pinjam']); ?></p>
                                    </div>
                                    <div>
                                        <a href="../Peminjaman/peminjaman.php" class="btn btn-primary text-white">Pinjam</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada peminjaman yang perlu dikembalikan.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pengembalian Belum Lunas -->
                <div class="mt-4">
                    <div class="card shadow-sm p-3 border-0 rounded-4" style="background-color: #fff8e6;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-box rounded-circle text-center" style="background-color: #ffe4b5; width: 50px; height: 50px;">
                                <i class="bx bx-credit-card text-warning" style="font-size: 24px; line-height: 50px;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Belum Lunas</h5>
                                <p class="text-muted small">Daftar pengembalian dengan denda belum lunas</p>
                            </div>
                        </div>
                        <hr>
                        <div>
                            <?php if ($stmtBelumLunas->rowCount() > 0): ?>
                                <?php while ($row = $stmtBelumLunas->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Kode: <?= htmlspecialchars($row['kode_kembali']); ?></h6>
                                            <p class="text-muted mb-0 small">Nama: <?= htmlspecialchars($row['nama']); ?></p>
                                            <p class="text-muted small">Denda: Rp<?= number_format($row['denda'], 2, ',', '.'); ?></p>
                                        </div>
                                        <div>
                                            <a href="../Pengembalian/pengembalian.php" class="btn btn-warning text-white">Denda</a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-muted">Tidak ada pengembalian yang belum lunas.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Konfirmasi Logout -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Konfirmasi Logout</h5>
                <!-- Tombol tutup dihilangkan -->
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="logout()">Logout</button>
            </div>
        </div>
    </div>
</div>
<script>
    // Fungsi untuk menampilkan gambar secara acak
    function displayRandomImage() {
        // Daftar nama file gambar di folder ../../Assets/Anime Date
        const images = ["anime1.jpg", "anime2.jpg", "anime3.jpg", "anime4.jpg", "anime5.jpg", "anime6.jpg"];

        // Pilih gambar secara acak
        const randomIndex = Math.floor(Math.random() * images.length);
        const selectedImage = images[randomIndex];

        // Update atribut src pada elemen img
        const imageElement = document.getElementById("random-image");
        imageElement.src = `../../Assets/Anime Date/${selectedImage}`;
    }

    // Jalankan fungsi saat halaman dimuat
    window.onload = displayRandomImage;

    function confirmLogout() {
        var myModal = new bootstrap.Modal(document.getElementById('logoutModal'));
        myModal.show();
    }

    function logout() {
        window.location.href = "logout.php"; // Redirect ke logout.php
    }

    const monthNames = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ];
    const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

    const calendarGrid = document.getElementById("calendar-grid");
    const monthYearLabel = document.getElementById("month-year");
    const prevButton = document.getElementById("prev");
    const nextButton = document.getElementById("next");

    let currentDate = new Date();

    function renderCalendar() {
        calendarGrid.innerHTML = "";

        // Set month and year
        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();
        monthYearLabel.textContent = `${monthNames[month]} ${year}`;

        // Create day headers
        dayNames.forEach(day => {
            const dayHeader = document.createElement("div");
            dayHeader.textContent = day;
            dayHeader.classList.add("day-header");
            calendarGrid.appendChild(dayHeader);
        });

        // First day of the month
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Create blank days
        for (let i = 0; i < firstDay; i++) {
            const blankDay = document.createElement("div");
            blankDay.classList.add("day");
            calendarGrid.appendChild(blankDay);
        }

        // Create actual days
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement("div");
            dayElement.textContent = day;
            dayElement.classList.add("day");

            // Highlight current day
            if (
                day === currentDate.getDate() &&
                month === new Date().getMonth() &&
                year === new Date().getFullYear()
            ) {
                dayElement.classList.add("current-day");
            }

            calendarGrid.appendChild(dayElement);
        }
    }

    // Navigate months
    prevButton.addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextButton.addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // Initial render
    renderCalendar();
</script>
<!-- Flatpickr Script -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>