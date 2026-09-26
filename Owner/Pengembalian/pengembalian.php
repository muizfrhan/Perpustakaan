<?php
require_once '../../Config/koneksi.php';
include '../Layouts/header.php';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page - 1) * $limit;


$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua'; // Menangani filter

$whereClause = '';
if ($filter === 'belum_lunas') {
  $whereClause = "WHERE pengembalian.status = 'Belum Lunas'";
} elseif ($filter === 'lunas') {
  $whereClause = "WHERE pengembalian.status = 'Lunas'";
}

// Hitung total data
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM pengembalian $whereClause");
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Update query untuk menambahkan kondisi WHERE berdasarkan filter
$result = $conn->prepare("
    SELECT pengembalian.kode_kembali, pengembalian.tgl_kembali, pengembalian.kode_pinjam, 
           pengembalian.kondisi_buku, pengembalian.denda, pengembalian.status, 
           pengembalian.pembayaran, anggota.nama AS nama_anggota, 
           anggota.no_telp, buku.judul_buku 
    FROM pengembalian
    JOIN peminjaman ON pengembalian.kode_pinjam = peminjaman.kode_pinjam
    JOIN anggota ON peminjaman.nim = anggota.nim
    JOIN buku ON peminjaman.kode_buku = buku.kode_buku
    $whereClause
    ORDER BY pengembalian.tgl_kembali DESC
    LIMIT :limit OFFSET :offset
");


$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();
?>

<section class="home-section">
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <!-- Judul -->
      <h2 class="fw-bold text-dark mb-0">Data Pengembalian</h2>

      <!-- Bagian Tombol dan Pencarian -->
      <div class="d-flex align-items-center gap-3">
        <!-- Filter Status -->
        <div class="dropdown">
          <!-- Tombol Filter -->
          <button class="btn btn-light border d-flex align-items-center"
            type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bx bx-filter-alt me-2"></i> Filter
          </button>
          <!-- Menu Dropdown -->
          <ul class="dropdown-menu rounded-3" aria-labelledby="filterDropdown">
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filter === 'semua' ? 'active' : ''; ?>" href="?filter=semua">
                <i class="bx bx-check-circle me-2"></i> Semua
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filter === 'belum_lunas' ? 'active' : ''; ?>" href="?filter=belum_lunas">
                <i class="bx bx-book-open me-2"></i> Belum Lunas
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filter === 'lunas' ? 'active' : ''; ?>" href="?filter=lunas">
                <i class="bx bx-check-circle me-2"></i> Lunas
              </a>
            </li>
          </ul>
        </div>

        <!-- Input Pencarian -->
        <div class="input-group rounded-3" style="max-width: 250px;">
          <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
          <input type="text" class="form-control" id="search" placeholder="Cari Pengembalian..." onkeyup="searchTable()">
        </div>
        <!-- Tombol Print -->
        <button class="btn btn-info shadow-sm rounded-3 text-white" onclick="printAll()">
          <i class="fas fa-print"></i> Cetak Semua
        </button>
      </div>
    </div>

    <div class="border border-secondary border-opacity-75 p-2 mb-2 rounded-3 overflow-hidden">
      <table class="table table-hover align-middle" id="pengembalianTable">
        <thead class="bg-primary text-white">
          <tr>
            <th class="text-center">Kode</th>
            <th>Nama Anggota</th>
            <th>Judul Buku</th>
            <th>Tgl.Kembali</th>
            <th>Kondisi</th>
            <th>Denda</th>
            <th>Status</th>
            <th>Pembayaran</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr style="font-size: 15px;">
              <td class="text-center"><?= $row['kode_kembali']; ?></td>
              <td style="font-weight: 600;"><?= $row['nama_anggota']; ?></td>
              <td><?= $row['judul_buku']; ?></td>
              <td><?= date('d-m-Y', strtotime($row['tgl_kembali'])); ?></td>
              <td>
                <?php if ($row['kondisi_buku'] == 'Bagus') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(72, 207, 255, 0.2); color: #48cfff; padding: 10px 20px; font-weight: bold; display: inline-block; text-align: center;">Bagus</span>
                <?php } elseif ($row['kondisi_buku'] == 'Rusak') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(255, 193, 7, 0.2); color: #ffc107; padding: 10px 20px; font-weight: bold; display: inline-block; text-align: center;">Rusak</span>
                <?php } else { ?>
                  <span class="badge rounded-4" style="background-color: rgba(244, 67, 54, 0.2); color: #f44336; padding: 10px 20px; font-weight: bold; display: inline-block; text-align: center;">Hilang</span>
                <?php } ?>
              </td>

              <td>Rp<?= number_format($row['denda'], 2, ',', '.'); ?></td>
              <td>
                <?php if ($row['status'] == 'Lunas') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(56, 193, 114, 0.2); color: #38c172; padding: 10px 10px; font-weight: bold; display: inline-block; text-align: center;">Lunas</span>
                <?php } else { ?>
                  <span class="badge rounded-4" style="background-color: rgba(244, 67, 54, 0.2); color: #f44336; padding: 10px 10px; font-weight: bold; display: inline-block; text-align: center;">Belum Lunas</span>
                <?php } ?>
              </td>

              <td>
                <?php if ($row['pembayaran'] == 'Tidak Ada') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(158, 158, 158, 0.2); color: #9e9e9e; padding: 10px 10px; font-weight: bold; display: inline-block; text-align: center;">Tidak Ada</span>
                <?php } elseif ($row['pembayaran'] == 'Kes') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(255, 193, 7, 0.2); color: #ffc107; padding: 10px 10px; font-weight: bold; display: inline-block; text-align: center;">Cash</span>
                <?php } else { ?>
                  <span class="badge rounded-4" style="background-color: rgba(33, 150, 243, 0.2); color: #2196f3; padding: 10px 10px; font-weight: bold; display: inline-block; text-align: center;">Transfer</span>
                <?php } ?>
              </td>
              <td class="text-center">
                <!-- Jika sudah Lunas hanya tampil tombol Print -->
                <button class="btn btn-info btn-sm text-white rounded-2" onclick="printData('<?= $row['kode_kembali']; ?>')">
                  <i class="fas fa-print"></i> Print
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Halaman dan Filter -->
      <nav class="mt-4">
        <ul class="pagination d-flex justify-content-between align-items-center">
          <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?filter=<?= $filter; ?>&page=<?= $page - 1; ?>">&laquo; Previous</a>
          </li>
          <div class="d-flex justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?filter=<?= $filter; ?>&page=<?= $i; ?>"><?= $i; ?></a>
              </li>
            <?php endfor; ?>
          </div>
          <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?filter=<?= $filter; ?>&page=<?= $page + 1; ?>">Next &raquo;</a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</section>


<script>
  // Fitur Searching
  function searchTable() {
    let input = document.getElementById("search").value.toLowerCase();
    let rows = document.querySelectorAll("#pengembalianTable tbody tr");
    rows.forEach((row) => {
      let text = row.innerText.toLowerCase();
      row.style.display = text.includes(input) ? "" : "none";
    });
  }

  // Fitur Print
  function printData(kode_kembali) {
    // Redirect ke halaman cetak atau buka pop-up untuk mencetak
    window.open(`print_pengembalian.php?kode_kembali=${kode_kembali}`, '_blank', 'width=800,height=600');
  }

  function printAll() {
    // Redirect ke halaman cetak seluruh data
    window.open('print_all_pengembalian.php', '_blank', 'width=800,height=600');
  }
</script>