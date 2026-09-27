<?php
/**
 * Form Tambah Buku.
 *
 * Dipakai dua kali:
 *   1. Dimuat AJAX ke dalam modal "Tambah Buku" (buku.php).
 *   2. Dikirim langsung sebagai form POST biasa.
 *
 * Nama field TIDAK BERUBAH agar tetap kompatibel dengan update_buku.php.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_buku = $_POST['kode_buku'];
    $judul_buku = $_POST['judul_buku'];
    $pengarang = $_POST['pengarang'];
    $penerbit = $_POST['penerbit'];
    $tanggal_terbit = $_POST['tanggal_terbit'];
    $bahasa = $_POST['bahasa'];
    $stok = $_POST['stok'];
    $kategori = $_POST['kategori'];
    $jumlah_halaman = $_POST['jumlah_halaman'];
    $deskripsi_buku = $_POST['deskripsi_buku'];

    // Handle file upload for cover
    $cover_name = $_FILES['cover']['name'];
    $cover_tmp = $_FILES['cover']['tmp_name'];
    $cover_folder = '../../Assets/uploads/' . $cover_name;

    try {
        // Cek apakah kode_buku sudah ada
        $stmt = $conn->prepare("SELECT COUNT(*) FROM buku WHERE kode_buku = ?");
        $stmt->execute([$kode_buku]);
        $kode_buku_exists = $stmt->fetchColumn();

        if ($kode_buku_exists > 0) {
            flash('danger', 'Kode Buku sudah terdaftar. Harap masukkan Kode Buku yang berbeda.');
            header('Location: buku.php');
            exit;
        }

        // Pindahkan file cover ke folder uploads jika ada
        if (!empty($cover_name)) {
            if (!move_uploaded_file($cover_tmp, $cover_folder)) {
                throw new Exception("Gagal mengupload file cover.");
            }
        } else {
            $cover_name = null; // Jika tidak ada file yang diunggah
        }

        // Jika kode_buku belum ada, lanjutkan proses simpan
        $stmt = $conn->prepare("INSERT INTO buku (kode_buku, judul_buku, pengarang, penerbit, tanggal_terbit, bahasa, stok, kategori, jumlah_halaman, deskripsi_buku, cover)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kode_buku, $judul_buku, $pengarang, $penerbit, $tanggal_terbit, $bahasa, $stok, $kategori, $jumlah_halaman, $deskripsi_buku, $cover_name]);

        flash('success', 'Buku berhasil ditambahkan.');
        header('Location: buku.php');
        exit;
    } catch (PDOException $e) {
        flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: buku.php');
        exit;
    }
}
?>
<form method="POST" action="add_buku.php" enctype="multipart/form-data" class="space-y-5" novalidate>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label for="kode_buku" class="field-label">Kode Buku <span class="text-rose-500">*</span></label>
      <input type="text" class="field" placeholder="Sesuai di belakang buku" id="kode_buku" name="kode_buku"
             data-autofocus required />
      <p class="field-hint">Gunakan kode unik, contoh: BK-001</p>
    </div>

    <div>
      <label for="judul_buku" class="field-label">Judul Buku <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="judul_buku" name="judul_buku"
             placeholder="Masukkan judul lengkap buku" required />
    </div>

    <div>
      <label for="pengarang" class="field-label">Pengarang <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="pengarang" name="pengarang"
             placeholder="Nama lengkap pengarang" required />
    </div>

    <div>
      <label for="penerbit" class="field-label">Penerbit <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="penerbit" name="penerbit"
             placeholder="Nama penerbit" required />
    </div>

    <div>
      <label for="tanggal_terbit" class="field-label">Tanggal Terbit <span class="text-rose-500">*</span></label>
      <input type="date" class="field" id="tanggal_terbit" name="tanggal_terbit" required />
    </div>

    <div>
      <label for="bahasa" class="field-label">Bahasa <span class="text-rose-500">*</span></label>
      <select class="field-select" id="bahasa" name="bahasa" required>
        <option value="" disabled selected>Pilih bahasa buku</option>
        <option value="Indonesia">Indonesia</option>
        <option value="Inggris">Inggris</option>
        <option value="Jawa">Jawa</option>
        <option value="Arab">Arab</option>
        <option value="Jepang">Jepang</option>
      </select>
    </div>

    <div>
      <label for="kategori" class="field-label">Kategori <span class="text-rose-500">*</span></label>
      <select class="field-select" id="kategori" name="kategori" required>
        <option value="" disabled selected>Pilih kategori buku</option>
        <option value="Pemrograman">Pemrograman</option>
        <option value="Jaringan dan Keamanan">Jaringan dan Keamanan</option>
        <option value="Algoritma dan Struktur Data">Algoritma dan Struktur Data</option>
        <option value="Basis Data">Basis Data</option>
        <option value="Kecerdasan Buatan">Kecerdasan Buatan</option>
      </select>
    </div>

    <div>
      <label for="stok" class="field-label">Stok <span class="text-rose-500">*</span></label>
      <input type="number" min="0" class="field" id="stok" name="stok" placeholder="0" required />
    </div>

    <div>
      <label for="jumlah_halaman" class="field-label">Jumlah Halaman <span class="text-rose-500">*</span></label>
      <input type="number" min="1" class="field" id="jumlah_halaman" name="jumlah_halaman" placeholder="0" required />
    </div>

    <div>
      <label for="cover" class="field-label">Cover Buku</label>
      <input type="file" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100
                     file:px-3 file:py-1.5 file:text-xs file:font-semibold
                     file:text-slate-700 hover:file:bg-slate-200"
             id="cover" name="cover" accept="image/*" />
      <p class="field-hint">Format JPG atau PNG, maksimal ukuran server.</p>
    </div>
  </div>

  <div>
    <label for="deskripsi_buku" class="field-label">Deskripsi Buku <span class="text-rose-500">*</span></label>
    <textarea class="field-textarea" id="deskripsi_buku" name="deskripsi_buku" rows="4"
              placeholder="Masukkan deskripsi singkat tentang buku" required></textarea>
  </div>

  <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
    <button type="reset" class="btn-secondary btn-sm">Reset</button>
    <button type="submit" class="btn-primary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Simpan Buku
    </button>
  </div>
</form>
