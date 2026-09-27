<?php
/**
 * Form Edit Anggota (fragment AJAX).
 *
 * Dimuat ke dalam #editModalContent oleh anggota.php.
 * Nama field TIDAK BERUBAH agar tetap kompatibel dengan update_anggota.php.
 */
require_once '../../Config/koneksi.php';
require_once __DIR__ . '/../../Config/bootstrap.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


$nim = $_GET['nim'] ?? '';

// Query untuk mendapatkan data anggota berdasarkan NIM.
// fetch() mengembalikan FALSE (bukan null) saat tidak ada baris, jadi
// gunakan ?: agar nilai kosong di bawahnya tetap aman diakses.
$stmt = $conn->prepare("SELECT * FROM anggota WHERE nim = ?");
$stmt->execute([$nim]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
  'nama' => '',
  'jenis_kelamin' => '',
  'kelas' => '',
  'tgl_lahir' => '',
  'jurusan' => '',
  'status_mhs' => '',
  'no_telp' => ''
];

$e = static fn($v) => htmlspecialchars((string) $v);
?>

<form method="POST" action="update_anggota.php" class="space-y-5" novalidate>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label for="nama" class="field-label">Nama Lengkap <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="nama" name="nama" value="<?= $e($anggota['nama']) ?>"
             data-autofocus required />
    </div>

    <div>
      <label for="jurusan" class="field-label">Jurusan <span class="text-rose-500">*</span></label>
      <select class="field-select" id="jurusan" name="jurusan" required>
        <option value="" disabled selected>Pilih Jurusan</option>
        <?php foreach ([
          'D4 Teknologi Rekayasa Perangkat Lunak',
          'S1 Teknik Informatika',
          'S1 Sistem Informasi',
          'D3 Teknik Komputer',
        ] as $j): ?>
          <option value="<?= $j ?>" <?= $anggota['jurusan'] === $j ? 'selected' : '' ?>><?= $j ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="kelas" class="field-label">Kelas <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="kelas" name="kelas" value="<?= $e($anggota['kelas']) ?>"
             placeholder="Contoh: 3A" required />
    </div>

    <div>
      <label for="tgl_lahir" class="field-label">Tanggal Lahir <span class="text-rose-500">*</span></label>
      <input type="date" class="field" id="tgl_lahir" name="tgl_lahir" value="<?= $e($anggota['tgl_lahir']) ?>" required />
    </div>

    <div>
      <label for="no_telp" class="field-label">No. Telpon <span class="text-rose-500">*</span></label>
      <input type="tel" class="field" id="no_telp" name="no_telp" value="<?= $e($anggota['no_telp']) ?>"
             placeholder="08xxxxxxxxxx" required />
    </div>

    <div>
      <label for="status_mhs" class="field-label">Status Mahasiswa <span class="text-rose-500">*</span></label>
      <select class="field-select" id="status_mhs" name="status_mhs" required>
        <option value="" disabled selected>Pilih Status</option>
        <option value="Aktif" <?= $anggota['status_mhs'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
        <option value="Tidak Aktif" <?= $anggota['status_mhs'] === 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
      </select>
    </div>

    <div class="sm:col-span-2">
      <span class="field-label">Jenis Kelamin <span class="text-rose-500">*</span></span>
      <div class="mt-1 flex flex-wrap gap-5">
        <?php foreach (['Laki-Laki' => 'jenis_kelamin_laki', 'Perempuan' => 'jenis_kelamin_perempuan'] as $value => $id): ?>
          <label class="check-label" for="<?= $id ?>">
            <input type="radio" class="check" name="jenis_kelamin" id="<?= $id ?>"
                   value="<?= $value ?>" <?= $anggota['jenis_kelamin'] === $value ? 'checked' : '' ?> required />
            <?= $value ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
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

  <input type="hidden" name="nim" value="<?= $e($nim) ?>" />
</form>
