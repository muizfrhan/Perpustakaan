<?php
require_once '../../Config/koneksi.php';

if (isset($_GET['id_petugas'])) {
    $id_petugas = $_GET['id_petugas'];

    // Ambil data petugas berdasarkan id_petugas
    $query = $conn->prepare("
        SELECT id_petugas, nama_petugas, username, no_telp, jenis_kelamin, profil_gambar, status 
        FROM petugas 
        WHERE id_petugas = :id_petugas
    ");
    $query->execute([':id_petugas' => $id_petugas]);
    $data = $query->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Format data
        $nama_petugas = $data['nama_petugas'];
        $username = $data['username'];
        $no_telp = $data['no_telp'];
        $jenis_kelamin = $data['jenis_kelamin'];
        $profil_gambar = $data['profil_gambar'];
        $status = $data['status'];
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ID Card Petugas</title>
            <style>
                /* Google Fonts */
                @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

                body {
                    font-family: 'Poppins', sans-serif;
                    background-color: #f0f2f5;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }

                .id-card {
                    width: 320px;
                    background: linear-gradient(135deg, #007bff, #00a8ff);
                    border-radius: 20px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                    overflow: hidden;
                    position: relative;
                    color: #fff;
                }

                .id-card-header {
                    padding: 20px;
                    text-align: center;
                    background: rgba(255, 255, 255, 0.1);
                }

                .id-card-header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                }

                .id-card-header p {
                    margin: 5px 0 0;
                    font-size: 14px;
                    font-weight: 400;
                }

                .id-card-body {
                    padding: 20px;
                    text-align: center;
                }

                .id-card-body img {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    border: 4px solid #fff;
                    margin-bottom: 15px;
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
                }

                .id-card-body h2 {
                    margin: 0;
                    font-size: 20px;
                    font-weight: 600;
                }

                .id-card-body p {
                    margin: 5px 0;
                    font-size: 14px;
                    font-weight: 400;
                }

                .id-card-footer {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 15px;
                    text-align: center;
                    border-top: 1px solid rgba(255, 255, 255, 0.2);
                }

                .id-card-footer p {
                    margin: 0;
                    font-size: 12px;
                    font-weight: 400;
                }

                .status-badge {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 500;
                    margin-top: 10px;
                    background: rgba(255, 255, 255, 0.2);
                    color: #fff;
                }

                .status-aktif {
                    background: rgba(56, 193, 114, 0.8);
                }

                .status-tidak-aktif {
                    background: rgba(244, 67, 54, 0.8);
                }

                .qr-code {
                    margin-top: 15px;
                    text-align: center;
                }

                .qr-code img {
                    width: 80px;
                    height: 80px;
                    border-radius: 10px;
                    background: #fff;
                    padding: 5px;
                }
            </style>
        </head>

        <body onload="window.print()">
            <div class="id-card">
                <!-- Header -->
                <div class="id-card-header">
                    <h1>ID CARD ADMIN</h1>
                </div>

                <!-- Body -->
                <div class="id-card-body">
                    <img src="../../Assets/uploads/<?= $profil_gambar; ?>" alt="Profil Petugas">
                    <h2><?= $nama_petugas; ?></h2>
                    <p><?= $username; ?></p>
                    <p><?= $no_telp; ?></p>
                    <p><?= $jenis_kelamin; ?></p>
                </div>

                <!-- Footer -->
                <div class="id-card-footer">
                    <p>Jl. Tasyuka No. 12, Kota Surakarta</p>
                    <p>www.pusakU.com</p>
                </div>
            </div>
        </body>

        </html>
<?php
    } else {
        echo "Data petugas tidak ditemukan.";
    }
} else {
    echo "ID petugas tidak ditemukan.";
}
?>