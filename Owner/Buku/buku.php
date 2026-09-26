<!-- buku.php -->
<?php
require_once '../../Config/koneksi.php';
include '../Layouts/header.php';

// Variabel filter
$filterStatus = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page - 1) * $limit;

// Query untuk menghitung total data dengan filter
$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM buku WHERE (:filterStatus = 'all' OR status = :filterStatus)");
$totalQuery->bindValue(':filterStatus', $filterStatus, PDO::PARAM_STR);
$totalQuery->execute();
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Query untuk mengambil data buku dengan filter
$result = $conn->prepare("SELECT * FROM buku WHERE (:filterStatus = 'all' OR status = :filterStatus) LIMIT :limit OFFSET :offset");
$result->bindValue(':filterStatus', $filterStatus, PDO::PARAM_STR);
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();
?>
<section class="home-section">
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <!-- Judul -->
      <h2 class="fw-bold text-dark mb-0">Data Buku</h2>

      <!-- Bagian Tombol dan Pencarian -->
      <div class="d-flex align-items-between gap-3">

        <!-- Tambahkan Dropdown Filter -->
        <div class="dropdown  rounded-3">
          <!-- Tombol Filter -->
          <button class="btn btn-light border d-flex align-items-center"
            type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bx bx-filter-alt me-2"></i> Filter
          </button>

          <ul class="dropdown-menu" aria-labelledby="filterDropdown">
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filterStatus === 'all' ? 'active' : ''; ?>" href="?filter=all">
                <i class="bx bx-check-circle me-2"></i> Semua
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filterStatus === 'Tersedia' ? 'active' : ''; ?>" href="?filter=Tersedia">
                <i class="bx bx-check-circle me-2"></i> Tersedia
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filterStatus === 'Dipinjam' ? 'active' : ''; ?>" href="?filter=Dipinjam">
                <i class="bx bx-book-open me-2"></i> Dipinjam
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filterStatus === 'Kosong' ? 'active' : ''; ?>" href="?filter=Kosong">
                <i class="bx bx-x-circle me-2"></i> Kosong
              </a>
            </li>
          </ul>

        </div>

        <!-- Input Pencarian -->
        <div class="input-group  rounded-3" style="max-width: 200px;">
          <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
          <input type="text" class="form-control" id="search" placeholder="Cari Buku..." onkeyup="searchTable()">
        </div>
      </div>
    </div>

    <div class="border border-secondary border-opacity-75 p-2 mb-2 rounded-3 overflow-hidden">
      <table class="table table-hover align-middle" id="bukuTable">
        <thead class="bg-primary text-white">
          <tr>
            <th class="text-center">Kode Buku</th>
            <th>Judul Buku</th>
            <th>Pengarang</th>
            <!-- <th>Penerbit</th> -->
            <th>Tanggal Terbit</th>
            <!-- <th>Jumlah Halaman</th> -->
            <!-- <th>Bahasa</th> -->
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr style="font-size: 15px;">
              <td class="text-center"><?= $row['kode_buku']; ?></td>
              <td style="font-weight: 600;"><?= $row['judul_buku']; ?></td>
              <td><?= $row['pengarang']; ?></td>
              <!-- <td><?= $row['penerbit']; ?></td> -->
              <td><?= $row['tanggal_terbit']; ?></td>
              <!-- <td><?= $row['jumlah_halaman']; ?></td> -->
              <!-- <td><?= $row['bahasa']; ?></td> -->
              <td class="text-center row-4">
                <button class="btn btn-info btn-sm rounded-2 text-white" data-bs-toggle="modal" data-bs-target="#detailBukuModal" onclick="loadDetailForm('<?= $row['kode_buku']; ?>')">
                  <i class="fas fa-info-circle"></i> Detail
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Navigasi Previous dan Next -->
      <nav class="mt-4">
        <ul class="pagination d-flex justify-content-between align-items-center me-4 ms-4">
          <!-- Tombol Previous -->
          <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?= $page - 1; ?>&filter=<?= $filterStatus; ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo; Previous</span>
            </a>
          </li>

          <!-- Nomor Halaman -->
          <div class="d-flex justify-content-center flex-grow-1">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?= $i; ?>&filter=<?= $filterStatus; ?>"><?= $i; ?></a>
              </li>
            <?php endfor; ?>
          </div>

          <!-- Tombol Next -->
          <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?= $page + 1; ?>&filter=<?= $filterStatus; ?>" aria-label="Next">
              <span aria-hidden="true">Next &raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>
    <!-- Modal Detail Buku -->
    <div class="modal fade" id="detailBukuModal" tabindex="-1" aria-labelledby="detailBukuLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="detailBukuLabel">Detail Buku</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="detailModalContent">
            <!-- Data Buku akan dimuat di sini menggunakan AJAX -->
          </div>
        </div>
      </div>
    </div>

</section>

<script>
  // Ajax Detail Buku
  function loadDetailForm(kode_buku) {
    const modalContent = document.getElementById('detailModalContent');
    modalContent.innerHTML = '<p class="text-center text-muted">Loading...</p>';
    fetch(`detail_buku.php?kode_buku=${kode_buku}`)
      .then(response => response.text())
      .then(data => {
        modalContent.innerHTML = data;
      })
      .catch(error => {
        modalContent.innerHTML = '<p class="text-danger">Gagal memuat data</p>';
      });
  }

  // Fitur Searching
  function searchTable() {
    let input = document.getElementById("search").value.toLowerCase();
    let rows = document.querySelectorAll("#bukuTable tbody tr");
    rows.forEach((row) => {
      let text = row.innerText.toLowerCase();
      row.style.display = text.includes(input) ? "" : "none";
    });
  }

  function applyFilter(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('status', status);
    url.searchParams.set('page', 1); // Reset ke halaman 1 setiap kali filter diubah
    window.location.href = url.toString();
  }
</script>