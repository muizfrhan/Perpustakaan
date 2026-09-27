<?php
/**
 * Formulir Tambah Anggota (dimuat via AJAX ke dalam modal).
 *
 * LOGIKA PENYIMPANAN & NAMA FIELD TIDAK BERUBAH.
 * action="add_anggota.php" dan nama field (nim, nama, jenis_kelamin,
 * kelas, tgl_lahir, jurusan, no_telp) dipertahankan.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    $nama = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $kelas = $_POST['kelas'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $jurusan = $_POST['jurusan'];
    $no_telp = $_POST['no_telp'];
    $status_mhs = 'Aktif'; // Default status mahasiswa

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM anggota WHERE nim = ?");
        $stmt->execute([$nim]);
        $nim_exists = $stmt->fetchColumn();

        if ($nim_exists > 0) {
            flash('danger', 'NIM sudah terdaftar. Harap masukkan NIM yang berbeda.');
            header('Location: anggota.php');
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO anggota (nim, nama, jenis_kelamin, kelas, tgl_lahir, jurusan, no_telp, status_mhs)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nim, $nama, $jenis_kelamin, $kelas, $tgl_lahir, $jurusan, $no_telp, $status_mhs]);

        flash('success', 'Anggota berhasil ditambahkan.');
        header('Location: anggota.php');
        exit;
    } catch (PDOException $e) {
        flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: anggota.php');
        exit;
    }
}
?>

<form method="POST" action="add_anggota.php" class="p-6">
  <div class="grid gap-5 sm:grid-cols-2">

    <div>
      <label for="nim" class="field-label">NIM</label>
      <input type="text" inputmode="numeric" id="nim" name="nim" required
             pattern="\d+" title="Hanya boleh angka"
             class="field" placeholder="Contoh: 230103161" />
    </div>

    <div>
      <label for="jurusan" class="field-label">Jurusan</label>
      <select id="jurusan" name="jurusan" required class="field">
        <option value="" disabled selected>Pilih Jurusan Anda</option>
        <option value="D4 Teknologi Rekayasa Perangkat Lunak">D4 Teknologi Rekayasa Perangkat Lunak</option>
        <option value="S1 Teknik Informatika">S1 Teknik Informatika</option>
        <option value="S1 Sistem Informasi">S1 Sistem Informasi</option>
        <option value="D3 Teknik Komputer">D3 Teknik Komputer</option>
      </select>
    </div>

    <div>
      <label for="nama" class="field-label">Nama Lengkap</label>
      <input type="text" id="nama" name="nama" required
             class="field" placeholder="Masukkan nama lengkap" />
    </div>

    <div>
      <label for="kelas" class="field-label">Kelas</label>
      <select id="kelas" name="kelas" required class="field">
        <option value="" disabled selected>Pilih Kelas</option>
      </select>
    </div>

    <div>
      <label for="no_telp" class="field-label">No. Telepon</label>
      <input type="text" id="no_telp" name="no_telp" required
             class="field" placeholder="Diawali +62 dan WA aktif" />
    </div>

    <div>
      <label for="tgl_lahir" class="field-label">Tanggal Lahir</label>
      <input type="date" id="tgl_lahir" name="tgl_lahir" required class="field" />
    </div>

    <fieldset class="sm:col-span-2">
      <legend class="field-label">Jenis Kelamin</legend>
      <div class="mt-2 grid grid-cols-2 gap-3">
        <label for="jenis_kelamin_laki"
               class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-4 py-3
                      transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
          <input type="radio" name="jenis_kelamin" id="jenis_kelamin_laki" value="Laki-Laki" required
                 class="h-4 w-4 accent-brand-600" />
          <span class="text-sm font-medium text-slate-700">Laki-Laki</span>
        </label>

        <label for="jenis_kelamin_perempuan"
               class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-4 py-3
                      transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
          <input type="radio" name="jenis_kelamin" id="jenis_kelamin_perempuan" value="Perempuan" required
                 class="h-4 w-4 accent-brand-600" />
          <span class="text-sm font-medium text-slate-700">Perempuan</span>
        </label>
      </div>
    </fieldset>

  </div>

  <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-5">
    <button type="reset" class="btn-secondary">Reset</button>
    <button type="submit" class="btn-primary">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      Simpan
    </button>
  </div>
</form>

<script>
  // NIM hanya boleh angka.
  const nimInput = document.getElementById('nim');
  nimInput?.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
  });
</script>
