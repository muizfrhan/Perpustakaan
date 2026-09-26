<?php
require_once '../../Config/koneksi.php';

if (isset($_GET['kode_pinjam'])) {
    $kode_pinjam = $_GET['kode_pinjam'];

    // Ambil data peminjaman berdasarkan ID
    $query = $conn->prepare("
        SELECT peminjaman.kode_pinjam, anggota.nama AS nama_anggota, buku.judul_buku, 
        petugas.nama_petugas, peminjaman.tgl_pinjam, peminjaman.estimasi_pinjam, peminjaman.kondisi_buku_pinjam 
        FROM peminjaman
        INNER JOIN anggota ON peminjaman.nim = anggota.nim
        INNER JOIN buku ON peminjaman.kode_buku = buku.kode_buku
        INNER JOIN petugas ON peminjaman.id_petugas = petugas.id_petugas
        WHERE peminjaman.kode_pinjam = :kode_pinjam
    ");
    $query->execute([':kode_pinjam' => $kode_pinjam]);
    $data = $query->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Format tanggal tanpa waktu
        $tgl_pinjam = date('Y-m-d', strtotime($data['tgl_pinjam']));
        $estimasi_pinjam = date('Y-m-d', strtotime($data['estimasi_pinjam']));
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Struk Peminjaman Buku</title>
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
                            <th>Kode Pinjam</th>
                            <td><?= $data['kode_pinjam']; ?></td>
                        </tr>
                        <tr>
                            <th>Nama Anggota</th>
                            <td><?= $data['nama_anggota']; ?></td>
                        </tr>
                        <tr>
                            <th>Judul Buku</th>
                            <td><?= $data['judul_buku']; ?></td>
                        </tr>
                        <tr>
                            <th>Nama Petugas</th>
                            <td><?= $data['nama_petugas']; ?></td>
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
                            <th>Kondisi Buku</th>
                            <td><?= $data['kondisi_buku_pinjam']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="footer">
                    <p>Terima kasih telah meminjam buku di Pusaku</p>
                    <p>Harap kembalikan buku tepat waktu.</p>
                </div>
            </div>
        </body>

        </html>
<?php
    } else {
        echo "Data tidak ditemukan.";
    }
} else {
    echo "ID peminjaman tidak ditemukan.";
}
?>