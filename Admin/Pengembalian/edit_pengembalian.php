<?php
session_start();
require_once '../../Config/koneksi.php';

if (isset($_GET['kode_kembali'])) {
    $kode_kembali = $_GET['kode_kembali'];

    // Ambil data pengembalian berdasarkan kode_kembali
    $stmt = $conn->prepare("SELECT * FROM pengembalian WHERE kode_kembali = :kode_kembali");
    $stmt->execute([':kode_kembali' => $kode_kembali]);
    $pengembalian = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pengembalian) {
        $_SESSION['message'] = "Data pengembalian tidak ditemukan.";
        header('Location: pengembalian.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_kembali = $_POST['kode_kembali'];
    $status = $_POST['status'];
    $pembayaran = $_POST['pembayaran'];

    // Update pengembalian
    $stmt = $conn->prepare("
        UPDATE pengembalian
        SET status = :status, pembayaran = :pembayaran
        WHERE kode_kembali = :kode_kembali
    ");
    try {
        $stmt->execute([
            ':status' => $status,
            ':pembayaran' => $pembayaran,
            ':kode_kembali' => $kode_kembali
        ]);

        $_SESSION['message'] = "Data pengembalian berhasil diperbarui!";
        header('Location: pengembalian.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Gagal memperbarui data pengembalian: " . $e->getMessage();
        header('Location: pengembalian.php');
        exit;
    }
}
?>

<div class="container">
  <form action="edit_pengembalian.php" method="POST">
    <input type="hidden" name="kode_kembali" value="<?= $pengembalian['kode_kembali']; ?>">

    <!-- Status -->
    <div class="mb-3">
      <label for="status" class="form-label">Status</label>
      <select name="status" id="status" class="form-select" required>
        <option value="Lunas" <?= $pengembalian['status'] == 'Lunas' ? 'selected' : ''; ?>>Lunas</option>
        <option value="Belum Lunas" <?= $pengembalian['status'] == 'Belum Lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
      </select>
    </div>

    <!-- Pembayaran -->
    <div class="mb-3">
      <label for="pembayaran" class="form-label">Pembayaran</label>
      <select name="pembayaran" id="pembayaran" class="form-select" required>
        <option value="Tidak Ada" <?= $pengembalian['pembayaran'] == 'Tidak Ada' ? 'selected' : ''; ?>>Tidak Ada</option>
        <option value="Kes" <?= $pengembalian['pembayaran'] == 'Kes' ? 'selected' : ''; ?>>Kes</option>
        <option value="Transfer" <?= $pengembalian['pembayaran'] == 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
      </select>
    </div>

    <button type="submit" class="btn btn-warning">Perbarui Pengembalian</button>
  </form>
</div>
