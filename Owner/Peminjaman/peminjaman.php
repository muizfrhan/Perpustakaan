<?php
require_once '../../Config/koneksi.php';
include '../Layouts/header.php';

// Pagination logic
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini
$offset = ($page - 1) * $limit;

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$whereClause = '';
if ($filter === 'dipinjam') {
  $whereClause = "WHERE NOT EXISTS (SELECT 1 FROM pengembalian WHERE pengembalian.kode_pinjam = peminjaman.kode_pinjam)";
} elseif ($filter === 'dikembalikan') {
  $whereClause = "WHERE EXISTS (SELECT 1 FROM pengembalian WHERE pengembalian.kode_pinjam = peminjaman.kode_pinjam)";
}

// Hitung total data
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM peminjaman $whereClause");
$totalResult = $totalQuery->fetch(PDO::FETCH_ASSOC);
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Ambil data sesuai halaman dan filter
$query = "
    SELECT peminjaman.kode_pinjam, anggota.nama AS nama_anggota, anggota.no_telp, buku.judul_buku, 
    petugas.nama_petugas, peminjaman.tgl_pinjam, peminjaman.estimasi_pinjam, 
    peminjaman.kondisi_buku_pinjam, 
    IF(EXISTS (SELECT 1 FROM pengembalian WHERE pengembalian.kode_pinjam = peminjaman.kode_pinjam), 'Dikembalikan', 'Dipinjam') AS status
    FROM peminjaman
    INNER JOIN anggota ON peminjaman.nim = anggota.nim
    INNER JOIN buku ON peminjaman.kode_buku = buku.kode_buku
    INNER JOIN petugas ON peminjaman.id_petugas = petugas.id_petugas
    $whereClause
    ORDER BY peminjaman.tgl_pinjam DESC
    LIMIT :limit OFFSET :offset
";

$result = $conn->prepare($query);
$result->bindValue(':limit', $limit, PDO::PARAM_INT);
$result->bindValue(':offset', $offset, PDO::PARAM_INT);
$result->execute();
?>

<section class="home-section">
  <div class="mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-dark mb-0">Data Peminjaman</h2>
      <div class="d-flex align-items-between gap-3">
        <!-- Filter Status -->
        <div class="dropdown rounded-3">
          <!-- Tombol Filter -->
          <button class="btn btn-light border d-flex align-items-center"
            type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bx bx-filter-alt me-2"></i> Filter
          </button>
          <!-- Menu Dropdown -->
          <ul class="dropdown-menu" aria-labelledby="filterDropdown">
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filter === 'semua' ? 'active' : ''; ?>" href="?filter=semua">
                <i class="bx bx-check-circle me-2"></i> Semua
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filter === 'dipinjam' ? 'active' : ''; ?>" href="?filter=dipinjam">
                <i class="bx bx-book-open me-2"></i> Dipinjam
              </a>
            </li>
            <li>
              <a class="dropdown-item d-flex align-items-center <?= $filter === 'dikembalikan' ? 'active' : ''; ?>" href="?filter=dikembalikan">
                <i class="bx bx-check-circle me-2"></i> Dikembalikan
              </a>
            </li>
          </ul>
        </div>

        <div class="input-group rounded-3" style="max-width: 250px;">
          <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
          <input type="text" class="form-control" id="search" placeholder="Cari Peminjaman..." onkeyup="searchTable()">
        </div>
      </div>
    </div>

    <div class="border border-secondary border-opacity-75 p-2 mb-2 rounded-3 overflow-hidden">
      <table class="table table-hover align-middle" id="peminjamanTable">
        <thead class="bg-primary text-white">
          <tr>
            <th class="text-center">Kode</th>
            <th>Nama Anggota</th>
            <th>Judul Buku</th>
            <th>Nama Petugas</th>
            <th>Tanggal</th>
            <th>Estimasi</th>
            <th>Kondisi</th>
            <th>Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
            <tr style="font-size: 15px;">
              <td class="text-center"><?= $row['kode_pinjam']; ?></td>
              <td style="font-weight: 600;"><?= $row['nama_anggota']; ?></td>
              <td><?= $row['judul_buku']; ?></td>
              <td><?= $row['nama_petugas']; ?></td>
              <td><?= date('d-m-Y', strtotime($row['tgl_pinjam'])); ?></td>
              <td style="<?php if (strtotime(date('Y-m-d')) > strtotime($row['estimasi_pinjam']) && $row['status'] === 'Dipinjam') echo 'color: red;'; ?>">
                <?= date('d-m-Y', strtotime($row['estimasi_pinjam'])); ?>
              </td>
              <td>
                <?php if ($row['kondisi_buku_pinjam'] == 'Bagus') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(72, 207, 255, 0.2); color: #48cfff; padding: 10px 20px; font-weight: bold; display: inline-block; width: 100px; height: 28px; text-align: center;">Bagus</span>
                <?php } else { ?>
                  <span class="badge rounded-4" style="background-color: rgba(255, 193, 7, 0.2); color: #ffc107; padding: 10px 20px; font-weight: bold; display: inline-block; width: 100px; height: 28px; text-align: center;">Rusak</span>
                <?php } ?>
              </td>
              <td>
                <?php if ($row['status'] === 'Dipinjam') { ?>
                  <span class="badge rounded-4" style="background-color: rgba(255, 152, 0, 0.2); color: #ff9800; padding: 10px 10px; font-weight: bold; display: inline-block; width: 100px; height: 28px; text-align: center;">Dipinjam</span>
                <?php } else { ?>
                  <span class="badge rounded-4" style="background-color: rgba(76, 175, 80, 0.2); color: #4caf50; padding: 10px 10px; font-weight: bold; display: inline-block; width: 100px; height: 28px; text-align: center;">Dikembalikan</span>
                <?php } ?>
              </td>
              <td class="text-center">
                <button class="btn btn-info btn-sm rounded-2 text-white mb-2" onclick="printData('<?= $row['kode_pinjam']; ?>')">
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
    let rows = document.querySelectorAll("#peminjamanTable tbody tr");
    rows.forEach((row) => {
      let text = row.innerText.toLowerCase();
      row.style.display = text.includes(input) ? "" : "none";
    });
  }

  //Fitur Print
  function printData(kode_pinjam) {
    // Redirect ke halaman cetak atau buka pop-up untuk mencetak
    window.open(`print_peminjaman.php?kode_pinjam=${kode_pinjam}`, '_blank', 'width=800,height=600');
  }

  function filterTable() {
    const status = document.getElementById("filterStatus").value;
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('filter', status);
    urlParams.set('page', 1); // Reset ke halaman pertama
    window.location.search = urlParams.toString();
  }
</script>