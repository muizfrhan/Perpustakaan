<?php
require_once '../../Config/koneksi.php';

// Ambil seluruh data pengembalian
$stmt = $conn->prepare("
    SELECT pengembalian.kode_kembali, pengembalian.tgl_kembali, pengembalian.kode_pinjam, 
           pengembalian.kondisi_buku, pengembalian.denda, pengembalian.status, 
           pengembalian.pembayaran, anggota.nama AS nama_anggota, 
           buku.judul_buku 
    FROM pengembalian
    JOIN peminjaman ON pengembalian.kode_pinjam = peminjaman.kode_pinjam
    JOIN anggota ON peminjaman.nim = anggota.nim
    JOIN buku ON peminjaman.kode_buku = buku.kode_buku
");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total penghasilan dari denda
$totalDenda = 0;
foreach ($data as $row) {
    $totalDenda += $row['denda'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cetak Semua Pengembalian</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table th, table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    table th {
      background-color: #f2f2f2;
    }
    .text-center {
      text-align: center;
    }
    .text-right {
      text-align: right;
    }
    .summary {
      margin-top: 20px;
      font-size: 1.1em;
    }
    .summary span {
      font-weight: bold;
    }
  </style>
</head>
<body>
  <h2 class="text-center">Laporan Seluruh Pengembalian</h2>
  <table>
    <thead>
      <tr>
        <th>Kode</th>
        <th>Nama Anggota</th>
        <th>Judul Buku</th>
        <th>Tanggal Kembali</th>
        <th>Kondisi Buku</th>
        <th>Denda</th>
        <th>Status</th>
        <th>Pembayaran</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data as $row): ?>
      <tr>
        <td><?= $row['kode_kembali']; ?></td>
        <td><?= $row['nama_anggota']; ?></td>
        <td><?= $row['judul_buku']; ?></td>
        <td><?= date('d-m-Y H:i', strtotime($row['tgl_kembali'])); ?></td>
        <td><?= $row['kondisi_buku']; ?></td>
        <td>Rp<?= number_format($row['denda'], 2, ',', '.'); ?></td>
        <td><?= $row['status']; ?></td>
        <td><?= $row['pembayaran']; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="summary">
    <p>Total Penghasilan Denda: <span>Rp<?= number_format($totalDenda, 2, ',', '.'); ?></span></p>
  </div>

  <script>
    // Cetak otomatis saat halaman dibuka
    window.print();
  </script>
</body>
</html>
