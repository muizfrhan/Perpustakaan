<?php
/**
 * Form Tambah Petugas (fragment AJAX - Panel Owner).
 *
 * Dimuat ke dalam #modalContent oleh admin.php.
 * action="add_petugas.php" dan nama field TIDAK BERUBAH agar tetap
 * kompatibel dengan proses simpan di bawah.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_owner();


use App\Services\Profil;

// Fungsi untuk mengambil ID petugas terbaru
function getLastPetugasId($conn)
{
  $stmt = $conn->prepare("SELECT MAX(id_petugas) AS last_id FROM petugas");
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  return $result ? $result['last_id'] : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Ambil data dari form
  $id_petugas = getLastPetugasId($conn) + 1;  // ID otomatis
  $nama_petugas = $_POST['nama_petugas'];
  $username = $_POST['username'];
  $password = $_POST['password'];
  $no_telp = $_POST['no_telp'];
  $jenis_kelamin = $_POST['jenis_kelamin'];
  $profil_gambar = ''; // diisi bila ada foto yang diunggah

  try {
    // Foto: lewat Profil::unggahFoto() supaya divalidasi berdasarkan isi
    // berkas, diberi nama acak, dan disimpan di folder yang tidak bisa
    // mengeksekusi skrip. Versi lama memakai basename() dari nama berkas
    // mentah, sehingga berkas bernama "x.php" tersimpan dan bisa dijalankan.
    if (!empty($_FILES['profil_gambar']['name'])) {
      $hasil = Profil::unggahFoto($_FILES['profil_gambar']);
      if ($hasil['ok']) {
        $profil_gambar = $hasil['nama'];
      } else {
        flash('danger', $hasil['pesan']);
        header('Location: admin.php');
        exit;
      }
    }

    // Menyimpan data petugas ke database tanpa status_petugas
    $stmt = $conn->prepare("INSERT INTO petugas (id_petugas, nama_petugas, username, password, no_telp, jenis_kelamin, profil_gambar)
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id_petugas, $nama_petugas, $username, $password, $no_telp, $jenis_kelamin, $profil_gambar]);


    flash('success', 'Petugas berhasil ditambahkan.');
    header('Location: admin.php');
    exit;
  } catch (PDOException $e) {
    flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
    header('Location: admin.php');
    exit;
  }
}
?>

<form method="POST" action="add_petugas.php" enctype="multipart/form-data" class="space-y-5" novalidate>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label for="nama_petugas" class="field-label">Nama Petugas <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="nama_petugas" name="nama_petugas"
             placeholder="Masukkan Nama Lengkap" required />
    </div>

    <div>
      <label for="username" class="field-label">Username <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="username" name="username"
             placeholder="Masukkan Username" required />
    </div>

    <div>
      <label for="password" class="field-label">Password <span class="text-rose-500">*</span></label>
      <input type="password" class="field" id="password" name="password"
             placeholder="Password min 8 dan ada karakter khusus" required />
      <p class="field-hint">Gunakan kombinasi huruf, angka, dan karakter khusus.</p>
    </div>

    <div>
      <label for="no_telp" class="field-label">No. Telepon <span class="text-rose-500">*</span></label>
      <input type="text" inputmode="numeric" class="field" id="no_telp" name="no_telp"
             placeholder="Diwali dengan '+62'" required />
    </div>

    <div class="sm:col-span-2">
      <span class="field-label">Jenis Kelamin <span class="text-rose-500">*</span></span>
      <div class="mt-1 flex flex-wrap gap-5">
        <label class="check-label" for="jenis_kelamin_laki">
          <input type="radio" class="check" name="jenis_kelamin" id="jenis_kelamin_laki"
                 value="Laki-Laki" required />
          Laki-Laki
        </label>
        <label class="check-label" for="jenis_kelamin_perempuan">
          <input type="radio" class="check" name="jenis_kelamin" id="jenis_kelamin_perempuan"
                 value="Perempuan" required />
          Perempuan
        </label>
      </div>
    </div>

    <div class="sm:col-span-2">
      <label for="profil_gambar" class="field-label">Foto Profil</label>
      <input type="file" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100
                     file:px-3 file:py-1.5 file:text-xs file:font-semibold
                     file:text-slate-700 hover:file:bg-slate-200"
             id="profil_gambar" name="profil_gambar" accept="image/*" />
      <p class="field-hint">Format JPG atau PNG, maksimal ukuran server.</p>
    </div>
  </div>

  <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-slate-200 pt-5">
    <button type="reset" class="btn-secondary btn-sm">Reset</button>
    <button type="submit" class="btn-primary btn-sm">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Simpan
    </button>
  </div>
</form>

<script>
  // No. telepon hanya boleh angka (jika form dibuka langsung, bukan via AJAX).
  const noTelpInput = document.getElementById('no_telp');
  if (noTelpInput) {
    noTelpInput.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, ''); // Menghapus karakter selain angka
    });
  }
</script>
