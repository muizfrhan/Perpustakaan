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

<div class="container">
    <form action="add_pengembalian.php" method="POST">
        <!-- Pencarian Kode Peminjaman -->
        <div class="mb-3">
            <label for="kode_pinjam" class="form-label">Kode Peminjaman</label>
            <input autocomplete="off" type="text" id="kode_pinjam" name="kode_pinjam" class="form-control" placeholder="Cari Kode Pnjam.." required>
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
        <div class="d-flex justify-content-end mt-4 rounded-3">
            <button type="reset" class="btn btn-danger me-2">Reset</button>
            <button type="submit" class="btn btn-primary">Proses</button>
        </div>

    </form>
</div>