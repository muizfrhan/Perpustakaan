<?php
/**
 * Form Edit Pengembalian (fragment AJAX).
 *
 * Dimuat ke dalam #editModalContent oleh pengembalian.php.
 * Nama field TIDAK BERUBAH agar tetap kompatibel dengan query UPDATE di bawah.
 */
session_start();
require_once '../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


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

$e = static fn($v) => htmlspecialchars((string) $v);
?>

<form action="edit_pengembalian.php" method="POST" class="space-y-5" novalidate>
  <input type="hidden" name="kode_kembali" value="<?= $e($pengembalian['kode_kembali']); ?>" />

  <div class="card-base p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <code class="code-chip"><?= $e($pengembalian['kode_kembali']); ?></code>
      <span class="whitespace-nowrap text-sm font-semibold text-slate-900">
        Denda: Rp<?= number_format((float) $pengembalian['denda'], 2, ',', '.') ?>
      </span>
    </div>
  </div>

  <!-- Status -->
  <div>
    <label for="status" class="field-label">Status <span class="text-rose-500">*</span></label>
    <select name="status" id="status" class="field-select" data-autofocus required>
      <option value="Lunas" <?= $pengembalian['status'] == 'Lunas' ? 'selected' : ''; ?>>Lunas</option>
      <option value="Belum Lunas" <?= $pengembalian['status'] == 'Belum Lunas' ? 'selected' : ''; ?>>Belum Lunas</option>
    </select>
    <p class="field-hint">Pilih <span class="font-medium text-slate-600">Lunas</span> setelah dendanya sudah dilunasi.</p>
  </div>

  <!-- Pembayaran -->
  <div>
    <label for="pembayaran" class="field-label">Pembayaran <span class="text-rose-500">*</span></label>
    <select name="pembayaran" id="pembayaran" class="field-select" required>
      <option value="Tidak Ada" <?= $pengembalian['pembayaran'] == 'Tidak Ada' ? 'selected' : ''; ?>>Tidak Ada</option>
      <option value="Kes" <?= $pengembalian['pembayaran'] == 'Kes' ? 'selected' : ''; ?>>Kes</option>
      <option value="Transfer" <?= $pengembalian['pembayaran'] == 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
    </select>
  </div>

  <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
    <button type="reset" class="btn-secondary btn-sm">Reset</button>
    <button type="submit" class="btn-warning btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Perbarui Pengembalian
    </button>
  </div>
</form>
