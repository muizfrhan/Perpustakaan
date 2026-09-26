<!-- admin.php -->
<?php
require_once '../../Config/koneksi.php';
include '../Layouts/header.php';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page - 1) * $limit;

// Filter status petugas
$statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'semua'; // Default 'semua'
$whereClause = '';
if ($statusFilter === 'aktif') {
  $whereClause = "WHERE status = 'Aktif'";
} elseif ($statusFilter === 'tidak_aktif') {
  $whereClause = "WHERE status = 'Tidak Aktif'";
}

// Hitung total data dengan filter
$totalQuery = $conn->prepare("SELECT COUNT(*) AS total FROM petugas $whereClause");
$totalQuery->execute();
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Ambil data sesuai filter dan halaman
$result = $conn->prepare("SELECT * FROM petugas $whereClause LIMIT :limit OFFSET :offset");
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();

?>
<section class="home-section">
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <!-- Judul -->
      <h2 class="fw-bold text-dark mb-0">Data Petugas</h2>

      <!-- Bagian Tombol dan Pencarian -->
      <div class="d-flex align-items-between gap-3">
        <div class="dropdown rounded-3">
          <button class="btn btn-light border d-flex align-items-center" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bx bx-filter-alt me-2"></i> Filter
          </button>
          <ul class="dropdown-menu" aria-labelledby="statusFilterDropdown">
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $statusFilter === 'semua' ? 'active' : ''; ?>" href="?status_filter=semua">
                <i class="bx bx-check-circle me-2"></i> Semua
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $statusFilter === 'aktif' ? 'active' : ''; ?>" href="?status_filter=aktif">
                <i class="bx bx-user-check me-2 text-success"></i> Aktif
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $statusFilter === 'tidak_aktif' ? 'active' : ''; ?>" href="?status_filter=tidak_aktif">
                <i class="bx bx-user-x me-2 text-danger"></i> Tidak Aktif
              </a>
            </li>
          </ul>
        </div>


        <!-- Input Pencarian -->
        <div class="input-group" style="max-width: 200px;">
          <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
          <input type="text" class="form-control" id="search" placeholder="Cari Petugas..." onkeyup="searchTable()">
        </div>

        <!-- Tombol Tambah Petugas -->
        <div>
          <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahPetugasModal">
            <i class="fas fa-plus"></i> Tambah
          </button>
        </div>
      </div>
    </div>

    <div class="border border-secondary border-opacity-75 p-2 mb-2 rounded-3 overflow-hidden">
      <table class="table table-hover align-middle" id="petugasTable">
        <thead class="bg-primary text-white">
          <tr>
            <th class="text-center">ID</th>
            <th>Profil</th>
            <th>Nama</th>
            <th>Username</th>
            <th>No. Telp</th>
            <th>Jenis Kelamin</th>
            <th class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
              <td class="text-center"><?= $row['id_petugas']; ?></td>
              <td>
                <img src="../../Assets/uploads/<?= $row['profil_gambar']; ?>" alt="Profil Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
              </td>

              <td><?= $row['nama_petugas']; ?></td>
              <td><?= $row['username']; ?></td>
              <td><?= $row['no_telp']; ?></td>
              <td><?= $row['jenis_kelamin']; ?></td>
              <td class="text-center">
                <?php if ($row['status'] == 'Aktif') { ?>
                  <span class="badge rounded-4" style="background-color:  #e8f8e8; color: #38c172; padding: 10px 10px; font-weight: bold; display: inline-block; width: 100px; height: 28px; text-align: center;">Aktif</span>
                <?php } else { ?>
                  <span class="badge rounded-4" style="background-color: #e2e3e5; color: #6c757d; padding: 10px 10px; font-weight: bold; display: inline-block; width: 100px; height: 28px; text-align: center;">Tidak Aktif</span>
                <?php } ?>
              </td>
              <td class="text-center">
                <?php if ($row['status'] == 'Aktif') { ?>
                  <button class="btn btn-danger btn-sm rounded-2" onclick="ubahStatus('<?= $row['id_petugas']; ?>')">
                    <i class="fas fa-user-slash"></i> Nonaktif
                  </button>
                  <button class="btn btn-warning btn-sm rounded-2" data-bs-toggle="modal" data-bs-target="#editPetugasModal" onclick="loadEditForm('<?= $row['id_petugas']; ?>')">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button class="btn btn-info btn-sm rounded-2 text-white" onclick="printPetugas('<?= $row['id_petugas']; ?>')">
                    <i class="fas fa-print"></i> Print
                  </button>
                <?php } else { ?>
                  <button class="btn btn-info btn-sm rounded-2 text-white" onclick="printPetugas('<?= $row['id_petugas']; ?>')">
                    <i class="fas fa-print"></i> Print
                  </button>
                <?php } ?>
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
            <a class="page-link" href="?page=<?= $page - 1; ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo; Previous</span>
            </a>
          </li>

          <!-- Nomor Halaman -->
          <div class="d-flex justify-content-center flex-grow-1">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
              </li>
            <?php endfor; ?>
          </div>

          <!-- Tombol Next -->
          <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?= $page + 1; ?>" aria-label="Next">
              <span aria-hidden="true">Next &raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Modal Tambah Petugas -->
    <div class="modal fade" id="tambahPetugasModal" tabindex="-1" aria-labelledby="tambahPetugasLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="tambahPetugasLabel">Tambah Petugas Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="modalContent">
            <!-- Form akan dimuat di sini menggunakan AJAX -->
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Edit Petugas -->
    <div class="modal fade" id="editPetugasModal" tabindex="-1" aria-labelledby="editPetugasLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-warning text-white">
            <h5 class="modal-title" id="editPetugasLabel">Edit Data Petugas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="editModalContent">
            <!-- Form akan dimuat di sini menggunakan AJAX -->
          </div>
        </div>
      </div>
    </div>
</section>

<script>
  // Function to print petugas
  function printPetugas(id_petugas) {
    const printWindow = window.open(`print_petugas.php?id_petugas=${id_petugas}`, '_blank');
    printWindow.focus();
  }

  //Ajax Delet Petugas
  function ubahStatus(idPetugas) {
    if (confirm("Apakah Anda yakin ingin Menonaktifkan akun Admin ini?")) {
      // Kirim permintaan ke server
      window.location.href = `delete_petugas.php?id_petugas=${idPetugas}`;
    }
  }

  // Ajax Edit Petugas
  function loadEditForm(id_petugas) {
    const modalContent = document.getElementById('editModalContent');
    modalContent.innerHTML = '<p class="text-center text -muted">Loading...</p>';
    fetch(`edit_petugas.php?id_petugas=${id_petugas}`)
      .then(response => response.text())
      .then(data => {
        modalContent.innerHTML = data;
      })
      .catch(error => {
        modalContent.innerHTML = '<p class="text-danger">Gagal memuat data</p>';
      });
  }

  // Ajax Tambah Anggota
  document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('tambahPetugasModal');
    const modalContent = document.getElementById('modalContent');
    modal.addEventListener('show.bs.modal', function() {
      fetch('add_petugas.php')
        .then(response => response.text())
        .then(data => {
          modalContent.innerHTML = data;
        })
        .catch(error => {
          modalContent.innerHTML = '<p class="text-danger">Gagal memuat form</p>';
        });
    });
  });

  // Fitur Searching
  function searchTable() {
    let input = document.getElementById("search").value.toLowerCase();
    let rows = document.querySelectorAll("#petugasTable tbody tr");
    rows.forEach((row) => {
      let text = row.innerText.toLowerCase();
      row.style.display = text.includes(input) ? "" : "none";
    });
  }
</script>