<?php
/**
 * Form Edit Buku (fragment AJAX).
 *
 * Dimuat ke dalam #editModalContent oleh buku.php.
 * Nama field TIDAK BERUBAH agar tetap kompatibel dengan update_buku.php.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


// Ambil data buku berdasarkan kode_buku
if (isset($_GET['kode_buku'])) {
    $kode_buku = $_GET['kode_buku'];

    try {
        $stmt = $conn->prepare("SELECT * FROM buku WHERE kode_buku = ?");
        $stmt->execute([$kode_buku]);
        $buku = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$buku) {
            flash('danger', 'Buku tidak ditemukan.');
            header('Location: buku.php');
            exit;
        }
    } catch (PDOException $e) {
        flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: buku.php');
        exit;
    }
}

// Proses update data buku
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_buku = $_POST['kode_buku'];
    $judul_buku = $_POST['judul_buku'];
    $pengarang = $_POST['pengarang'];
    $penerbit = $_POST['penerbit'];
    $tanggal_terbit = $_POST['tanggal_terbit'];
    $bahasa = $_POST['bahasa'];
    $kategori = $_POST['kategori'];
    $stok = $_POST['stok'];
    $jumlah_halaman = $_POST['jumlah_halaman'];
    $deskripsi_buku = $_POST['deskripsi_buku'];

    // Handle file upload for cover
    $cover_name = $_FILES['cover']['name'];
    $cover_tmp = $_FILES['cover']['tmp_name'];
    $cover_folder = '../../Assets/uploads/' . $cover_name;

    try {
        // Pindahkan file cover jika ada
        if (!empty($cover_name)) {
            if (!move_uploaded_file($cover_tmp, $cover_folder)) {
                throw new Exception("Gagal mengupload file cover.");
            }
        } else {
            $cover_name = $buku['cover']; // Gunakan cover lama jika tidak diubah
        }

        // Update data buku
        $stmt = $conn->prepare("UPDATE buku SET judul_buku = ?, pengarang = ?, penerbit = ?, tanggal_terbit = ?, bahasa = ?, kategori = ?, jumlah_halaman = ?, stok = ?, deskripsi_buku = ?, cover = ? WHERE kode_buku = ?");
        $stmt->execute([$judul_buku, $pengarang, $penerbit, $tanggal_terbit, $bahasa, $kategori, $stok, $jumlah_halaman, $deskripsi_buku, $cover_name, $kode_buku]);

        flash($stmt->rowCount() > 0 ? 'success' : 'info',
              $stmt->rowCount() > 0 ? 'Buku berhasil diperbarui.' : 'Tidak ada perubahan yang dilakukan.');
        header('Location: buku.php');
        exit;
    } catch (PDOException $e) {
        flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: buku.php');
        exit;
    }
}

$e = static fn($v) => htmlspecialchars((string) $v);
?>

<form method="POST" action="update_buku.php" enctype="multipart/form-data" class="space-y-5" novalidate>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label for="kode_buku" class="field-label">Kode Buku</label>
      <input type="text" class="field cursor-not-allowed bg-slate-50 text-slate-500"
             id="kode_buku" name="kode_buku" value="<?= $e($buku['kode_buku']) ?>" readonly />
      <p class="field-hint">Kode buku tidak dapat diubah.</p>
    </div>

    <div>
      <label for="judul_buku" class="field-label">Judul Buku <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="judul_buku" name="judul_buku"
             value="<?= $e($buku['judul_buku']) ?>" data-autofocus required />
    </div>

    <div>
      <label for="pengarang" class="field-label">Pengarang <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="pengarang" name="pengarang"
             value="<?= $e($buku['pengarang']) ?>" required />
    </div>

    <div>
      <label for="penerbit" class="field-label">Penerbit <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="penerbit" name="penerbit"
             value="<?= $e($buku['penerbit']) ?>" required />
    </div>

    <div>
      <label for="tanggal_terbit" class="field-label">Tanggal Terbit <span class="text-rose-500">*</span></label>
      <input type="date" class="field" id="tanggal_terbit" name="tanggal_terbit"
             value="<?= $e($buku['tanggal_terbit']) ?>" required />
    </div>

    <div>
      <label for="bahasa" class="field-label">Bahasa <span class="text-rose-500">*</span></label>
      <select class="field-select" id="bahasa" name="bahasa" required>
        <?php foreach (['Indonesia', 'Inggris', 'Jawa', 'Arab', 'Jepang'] as $b): ?>
          <option value="<?= $b ?>" <?= $buku['bahasa'] === $b ? 'selected' : '' ?>><?= $b ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="kategori" class="field-label">Kategori <span class="text-rose-500">*</span></label>
      <select class="field-select" id="kategori" name="kategori" required>
        <?php foreach (['Pemrograman', 'Jaringan dan Keamanan', 'Algoritma dan Struktur Data', 'Basis Data', 'Kecerdasan Buatan'] as $k): ?>
          <option value="<?= $k ?>" <?= $buku['kategori'] === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="jumlah_halaman" class="field-label">Jumlah Halaman <span class="text-rose-500">*</span></label>
      <input type="number" min="1" class="field" id="jumlah_halaman" name="jumlah_halaman"
             value="<?= $e($buku['jumlah_halaman']) ?>" required />
    </div>

    <div>
      <label for="stok" class="field-label">Stok <span class="text-rose-500">*</span></label>
      <input type="number" min="0" class="field" id="stok" name="stok"
             value="<?= $e($buku['stok']) ?>" required />
    </div>

    <div>
      <label for="cover" class="field-label">Ganti Cover</label>
      <input type="file" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100
                     file:px-3 file:py-1.5 file:text-xs file:font-semibold
                     file:text-slate-700 hover:file:bg-slate-200"
             id="cover" name="cover" accept="image/*" />
      <p class="field-hint">
        Cover saat ini:
        <?= !empty($buku['cover'])
            ? htmlspecialchars((string) $buku['cover'])
            : '<span class="italic">tidak ada</span>' ?>
      </p>
    </div>
  </div>

  <div>
    <label for="deskripsi_buku" class="field-label">Deskripsi Buku <span class="text-rose-500">*</span></label>
    <textarea class="field-textarea" id="deskripsi_buku" name="deskripsi_buku" rows="4" required><?= $e($buku['deskripsi_buku']) ?></textarea>
  </div>

  <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
    <button type="reset" class="btn-secondary btn-sm">Reset</button>
    <button type="submit" class="btn-primary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Simpan Perubahan
    </button>
  </div>
</form>
