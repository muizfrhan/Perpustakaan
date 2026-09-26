<?php
require_once '../../Config/koneksi.php';

if (isset($_GET['kode_kembali'])) {
  $kode_kembali = $_GET['kode_kembali'];

  // Ambil data pengembalian berdasarkan kode_kembali
  $query = $conn->prepare("
        SELECT pengembalian.kode_kembali, pengembalian.tgl_kembali, pengembalian.kode_pinjam, 
               pengembalian.kondisi_buku, pengembalian.denda, pengembalian.status, 
               pengembalian.pembayaran, anggota.nama AS nama_anggota, 
               anggota.no_telp, buku.judul_buku, peminjaman.tgl_pinjam, peminjaman.estimasi_pinjam
        FROM pengembalian
        JOIN peminjaman ON pengembalian.kode_pinjam = peminjaman.kode_pinjam
        JOIN anggota ON peminjaman.nim = anggota.nim
        JOIN buku ON peminjaman.kode_buku = buku.kode_buku
        WHERE pengembalian.kode_kembali = :kode_kembali
    ");
  $query->execute([':kode_kembali' => $kode_kembali]);
  $data = $query->fetch(PDO::FETCH_ASSOC);

  if ($data) {
    // Format tanggal tanpa waktu
    $tgl_kembali = date('Y-m-d', strtotime($data['tgl_kembali']));
    $tgl_pinjam = date('Y-m-d', strtotime($data['tgl_pinjam']));
    $estimasi_pinjam = date('Y-m-d', strtotime($data['estimasi_pinjam']));

    // Hitung keterlambatan (jika ada)
    $terlambat = 0;
    if (strtotime($tgl_kembali) > strtotime($estimasi_pinjam)) {
      $terlambat = floor((strtotime($tgl_kembali) - strtotime($estimasi_pinjam)) / (60 * 60 * 24));
    }

    // Tentukan alasan denda
    $alasan_denda = [];
    if ($terlambat > 0) {
      $alasan_denda[] = "Terlambat mengembalikan ($terlambat hari)";
    }
    if ($data['kondisi_buku'] === 'Rusak') {
      $alasan_denda[] = "Buku rusak";
    }
    if ($data['kondisi_buku'] === 'Hilang') {
      $alasan_denda[] = "Buku hilang";
    }
    $keterangan_denda = !empty($alasan_denda) ? implode(", ", $alasan_denda) : "Tidak ada denda";
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Struk Pengembalian Buku</title>
      <style>
        body {
          font-family: 'Arial', sans-serif;
          background-color: #f9f9f9;
          margin: 0;
          padding: 0;
        }

        .struk {
          width: 300px;
          margin: 20px auto;
          background-color: #fff;
          border: 1px solid #ddd;
          box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
          padding: 20px;
          border-radius: 8px;
        }

        .header {
          text-align: center;
          margin-bottom: 20px;
        }

        .header img {
          max-width: 100px;
          margin-bottom: 10px;
        }

        .header h1 {
          font-size: 18px;
          margin: 0;
          color: #333;
        }

        .header p {
          font-size: 12px;
          color: #777;
          margin: 5px 0;
        }

        .details {
          font-size: 14px;
          color: #333;
        }

        .details table {
          width: 100%;
          border-collapse: collapse;
        }

        .details table th,
        .details table td {
          padding: 8px 0;
          border-bottom: 1px solid #ddd;
        }

        .details table th {
          text-align: left;
          font-weight: bold;
          width: 40%;
        }

        .details table td {
          text-align: right;
        }

        .footer {
          text-align: center;
          margin-top: 20px;
          font-size: 12px;
          color: #777;
        }
      </style>
    </head>

    <body onload="window.print()">
      <div class="struk">
        <div class="header">
          <!-- Logo Perpustakaan -->
          <img src="../../Assets/img/logo2.svg" alt="Logo Perpustakaan">
          <h1>Perpustakaan Pusaku</h1>
          <p>Jl. Tasyuka No. 12, Kota Surakarta</p>
        </div>
        <div class="details">
          <table>
            <tr>
              <th>Kode Kembali</th>
              <td><?= $data['kode_kembali']; ?></td>
            </tr>
            <tr>
              <th>Nama</th>
              <td><?= $data['nama_anggota']; ?></td>
            </tr>
            <tr>
              <th>Judul Buku</th>
              <td><?= $data['judul_buku']; ?></td>
            </tr>
            <tr>
              <th>Tanggal Pinjam</th>
              <td><?= $tgl_pinjam; ?></td>
            </tr>
            <tr>
              <th>Estimasi Kembali</th>
              <td><?= $estimasi_pinjam; ?></td>
            </tr>
            <tr>
              <th>Tanggal Kembali</th>
              <td><?= $tgl_kembali; ?></td>
            </tr>
            <tr>
              <th>Kondisi Buku</th>
              <td><?= $data['kondisi_buku']; ?></td>
            </tr>
            <tr>
              <th>Denda</th>
              <td>Rp<?= number_format($data['denda'], 2, ',', '.'); ?></td>
            </tr>
            <tr>
              <th>Keterangan Denda</th>
              <td><?= $keterangan_denda; ?></td>
            </tr>
            <tr>
              <th>Status</th>
              <td><?= $data['status']; ?></td>
            </tr>
            <tr>
              <th>Pembayaran</th>
              <td><?= $data['pembayaran']; ?></td>
            </tr>
          </table>
        </div>
        <div class="footer">
          <p>Terima kasih telah mengembalikan buku di Pusaku</p>
          <p>Harap periksa kembali data pengembalian Anda.</p>
        </div>
      </div>
    </body>

    </html>
<?php
  } else {
    echo "Data tidak ditemukan.";
  }
} else {
  echo "Kode pengembalian tidak ditemukan.";
}
?>