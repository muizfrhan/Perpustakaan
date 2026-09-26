<?php
session_start();  // Memastikan sesi dimulai
require_once '../../Config/koneksi.php';  // Menghubungkan ke file koneksi database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_pinjam = $_POST['kode_pinjam'];
    $kondisi_buku = $_POST['kondisi_buku']; // Menambahkan input kondisi buku
    $tgl_kembali = date('Y-m-d'); // Tanggal saat ini sebagai tanggal kembali
    $denda = 0;
    $status_pembayaran = 'Tidak Ada'; // Default pembayaran tidak ada
    $status = 'Lunas'; // Default status lunas

    // Ambil data peminjaman berdasarkan kode_pinjam
    $stmt = $conn->prepare("
        SELECT tgl_pinjam, kode_buku, estimasi_pinjam
        FROM peminjaman 
        WHERE kode_pinjam = :kode_pinjam
    ");
    $stmt->execute([':kode_pinjam' => $kode_pinjam]);
    $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$peminjaman) {
        $_SESSION['message'] = "Kode peminjaman tidak ditemukan.";
        header('Location: pengembalian.php');
        exit;
    }

    $kode_buku = $peminjaman['kode_buku']; // Ambil ID buku untuk pembaruan stok
    $estimasi_pinjam = $peminjaman['estimasi_pinjam']; // Ambil estimasi pinjam

    // Hitung keterlambatan
    $hari_terlambat = max((strtotime($tgl_kembali) - strtotime($estimasi_pinjam)) / (60 * 60 * 24), 0);

    // Jika tidak terlambat, set status dan pembayaran
    if ($hari_terlambat <= 0) {
        $status = 'Lunas';
        $status_pembayaran = 'Tidak Ada'; // Tidak ada pembayaran jika tidak terlambat
    } else {
        $status = 'Belum Lunas';
        $denda += $hari_terlambat * 5000; // Tambahkan denda Rp5.000 per hari keterlambatan
    }

    // Hitung denda tambahan berdasarkan kondisi buku
    if ($kondisi_buku === 'rusak') {
        $denda += 20000; // Denda Rp20.000 untuk buku rusak
        $status = 'Belum Lunas'; // Harus belum lunas jika buku rusak
    } elseif ($kondisi_buku === 'hilang') {
        $denda += 50000; // Denda Rp50.000 untuk buku hilang
        $status = 'Belum Lunas'; // Harus belum lunas jika buku hilang
    }

    // Generate kode_kembali otomatis
    $lastKode = $conn->query("
        SELECT kode_kembali 
        FROM pengembalian 
        ORDER BY kode_kembali DESC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    $newNumber = isset($lastKode['kode_kembali']) ? (int)substr($lastKode['kode_kembali'], 2) + 1 : 1;
    $kode_kembali = "KB" . str_pad($newNumber, 3, "0", STR_PAD_LEFT);

    // Simpan ke tabel pengembalian
    $stmt = $conn->prepare("
        INSERT INTO pengembalian (kode_kembali, kode_pinjam, tgl_kembali, kondisi_buku, denda, status, pembayaran) 
        VALUES (:kode_kembali, :kode_pinjam, :tgl_kembali, :kondisi_buku, :denda, :status, :pembayaran)
    ");
    try {
        $stmt->execute([
            ':kode_kembali' => $kode_kembali,
            ':kode_pinjam' => $kode_pinjam,
            ':tgl_kembali' => $tgl_kembali,
            ':kondisi_buku' => $kondisi_buku,
            ':denda' => $denda,
            ':status' => $status,
            ':pembayaran' => $status_pembayaran
        ]);

        // Pembaruan stok buku berdasarkan kondisi buku
        if ($kondisi_buku === 'bagus' || $kondisi_buku === 'rusak') {
            $conn->prepare("
                UPDATE buku 
                SET stok = stok + 1 
                WHERE kode_buku = :kode_buku
            ")->execute([':kode_buku' => $kode_buku]);
        }
        // Jika kondisi buku hilang, stok tidak diubah

        $_SESSION['message'] = "Pengembalian berhasil ditambahkan! Kode Kembali: $kode_kembali, Denda: Rp" . number_format($denda, 0, ',', '.');
        header('Location: pengembalian.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Gagal menambahkan pengembalian: " . $e->getMessage();
        header('Location: pengembalian.php');
        exit;
    }
}

// Ambil data peminjaman yang belum dikembalikan
$peminjaman = $conn->query("
    SELECT kode_pinjam 
    FROM peminjaman 
    WHERE kode_pinjam NOT IN (SELECT kode_pinjam FROM pengembalian)
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flatpickr Stylesheet -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <script src="../../Assets/scripts/header.js" defer></script>
    <link rel="stylesheet" href="../../Assets/css/header.css">
    <link rel="stylesheet" href="../../Assets/css/dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .calendar {
            background: #fff;
            border-radius: 25px;
            width: 800px;
            padding: 20px;
        }

        .calendar-header h2 {
            font-size: 1.5rem;
            margin: 0;
        }

        .calendar-header button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 10px 10px;
            border-radius: 100px;
            cursor: pointer;
        }

        .calendar-header button:hover {
            background: #0056b3;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
        }

        .day {
            padding: 11px 0;
            background: #e9ecef;
            border-radius: 25px;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .day:hover {
            background: #007bff;
            color: #fff;
            transform: scale(1.1);
        }

        .day-header {
            font-weight: bold;
            color: #495057;
        }

        .current-day {
            background: #007bff;
            color: #fff;
        }

        .autocomplete-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }

        .autocomplete-item:hover {
            background-color: #f1f1f1;
        }

        #search_results {
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
    </style>
</head>

<body>
    <div class="sidebar shadow ms">
        <div class="logo-details mb-4">
            <div class="logo_name">
                <img src="../../Assets/img/LogoPusaku.png" alt="" class="logo-img">
            </div>
            <i class='bx bx-menu' id="btn"></i>
        </div>
        <ul class="nav-list">
            <li>
                <a href="../Dashboard/dashboard.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="links_name">Dashboard</span>
                </a>
                <span class="tooltip">Dashboard</span>
            </li>
            <li>
                <a href="../Anggota/anggota.php" class="active">
                    <i class='bx bx-user'></i>
                    <span class="links_name">Anggota</span>
                </a>
                <span class="tooltip">Anggota</span>
            </li>
            <li>
                <a href="../Buku/buku.php">
                    <i class='bx bx-book'></i>
                    <span class="links_name">Buku</span>
                </a>
                <span class="tooltip">Buku</span>
            </li>
            <li>
                <a href="../Peminjaman/peminjaman.php">
                    <i class='bx bx-book-add'></i>
                    <span class="links_name">Peminjaman</span>
                </a>
                <span class="tooltip">Peminjaman</span>
            </li>
            <li>
                <a href="../Pengembalian/pengembalian.php">
                    <i class='bx bx-book-bookmark'></i>
                    <span class="links_name">Pengembalian</span>
                </a>
                <span class="tooltip">Pengembalian</span>
            </li>
        </ul>
    </div>
    <div class="container">
        <form action="add_pengembalian.php" method="POST">
            <!-- Pencarian Kode Peminjaman -->
            <div class="mb-3">
                <label for="kode_pinjam" class="form-label">Kode Peminjaman</label>
                <input type="text" id="kode_pinjam" name="kode_pinjam" class="form-control" required>
                <div id="search_results" class="mt-2"></div> <!-- Menampilkan hasil pencarian -->
            </div>

            <!-- Kondisi Buku -->
            <div class="mb-3">
                <label for="kondisi_buku" class="form-label">Kondisi Buku</label>
                <select name="kondisi_buku" id="kondisi_buku" class="form-select" required>
                    <option value="bagus">Bagus</option>
                    <option value="rusak">Rusak</option>
                    <option value="hilang">Hilang</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Proses Pengembalian</button>
        </form>
    </div>

    <script>
        // Fungsi untuk menangani autocomplete
        function setupAutocomplete() {
            const inputKodePinjam = document.getElementById('kode_pinjam');
            const searchResults = document.getElementById('search_results');

            inputKodePinjam.addEventListener('input', function() {
                const query = inputKodePinjam.value;

                if (query.length > 0) {
                    fetch(`search_kode_pinjam.php?kode_pinjam=${query}`)
                        .then(response => response.json())
                        .then(data => {
                            let html = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    html += `<div class="autocomplete-item" onclick="selectKodePinjam('${item.kode_pinjam}', '${item.nama_anggota}')">
                                    <strong> ${item.kode_pinjam} - </strong> ${item.nama_anggota} <br>
                                </div>`;
                                });
                            } else {
                                html = '<div class="autocomplete-item">Tidak ada hasil yang ditemukan</div>';
                            }
                            searchResults.innerHTML = html;
                            searchResults.style.display = 'block';
                        });
                } else {
                    searchResults.innerHTML = '';
                    searchResults.style.display = 'none';
                }
            });

            // Fungsi untuk memilih item dari hasil autocomplete
            window.selectKodePinjam = function(kodePinjam, namaAnggota) {
                inputKodePinjam.value = kodePinjam;
                searchResults.innerHTML = '';
                searchResults.style.display = 'none';
            };
        }

        // Panggil fungsi setupAutocomplete saat halaman dimuat
        document.addEventListener('DOMContentLoaded', setupAutocomplete);
        console.log('Autocomplete script berjalan!');
        const inputKodePinjam = document.getElementById('kode_pinjam');
        if (inputKodePinjam) {
            console.log('Elemen ditemukan:', inputKodePinjam);
        } else {
            console.log('Elemen #kode_pinjam tidak ditemukan!');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>