<?php
/**
 * Form Edit Petugas (fragment AJAX - Panel Owner).
 *
 * Dimuat ke dalam #editModalContent oleh admin.php.
 * action="edit_petugas.php?id_petugas=..." dan nama field
 * TIDAK BERUBAH agar tetap kompatibel dengan proses update di bawah.
 */
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_owner();


use App\Services\Profil;

// Ambil data petugas berdasarkan ID
$id_petugas = isset($_GET['id_petugas']) ? $_GET['id_petugas'] : null;
if (!$id_petugas) {
    flash('danger', 'ID petugas tidak ditemukan.');
    header('Location: admin.php');
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM petugas WHERE id_petugas = ?");
    $stmt->execute([$id_petugas]);
    $petugas = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$petugas) {
        flash('danger', 'Data petugas tidak ditemukan.');
        header('Location: admin.php');
        exit;
    }
} catch (PDOException $e) {
    flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_petugas = $_POST['nama_petugas'];
    $username = $_POST['username'];
    $no_telp = $_POST['no_telp'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $profil_gambar = $petugas['profil_gambar']; // Default gambar sebelumnya

    try {
        // Foto: lewat Profil::unggahFoto() (validasi isi berkas + nama acak).
        // Lihat catatan di add_petugas.php kenapa ini penting.
        if (!empty($_FILES['profil_gambar']['name'])) {
            $hasil = Profil::unggahFoto($_FILES['profil_gambar']);
            if ($hasil['ok']) {
                if ($profil_gambar && $profil_gambar !== $hasil['nama']) {
                    Profil::hapusFoto($profil_gambar);
                }
                $profil_gambar = $hasil['nama'];
            } else {
                flash('danger', $hasil['pesan']);
                header('Location: admin.php');
                exit;
            }
        }

        // Update data petugas ke database
        $stmt = $conn->prepare("UPDATE petugas
                                SET nama_petugas = ?, username = ?, no_telp = ?, jenis_kelamin = ?, profil_gambar = ?
                                WHERE id_petugas = ?");
        $stmt->execute([$nama_petugas, $username, $no_telp, $jenis_kelamin, $profil_gambar, $id_petugas]);

        flash('success', 'Petugas berhasil diperbarui.');
        header('Location: admin.php');
        exit;
    } catch (PDOException $e) {
        flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: admin.php');
        exit;
    }
}

$e = static fn($v) => htmlspecialchars((string) $v);
?>

<form method="POST" action="edit_petugas.php?id_petugas=<?= urlencode((string) $id_petugas) ?>"
      enctype="multipart/form-data" class="space-y-5" novalidate>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label for="nama_petugas" class="field-label">Nama Petugas <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="nama_petugas" name="nama_petugas"
             value="<?= $e($petugas['nama_petugas']) ?>" required />
    </div>

    <div>
      <label for="username" class="field-label">Username <span class="text-rose-500">*</span></label>
      <input type="text" class="field" id="username" name="username"
             value="<?= $e($petugas['username']) ?>" required />
    </div>

    <div>
      <label for="no_telp" class="field-label">No. Telepon <span class="text-rose-500">*</span></label>
      <input type="text" inputmode="numeric" class="field" id="no_telp" name="no_telp"
             value="<?= $e($petugas['no_telp']) ?>" required />
    </div>

    <div>
      <span class="field-label">Jenis Kelamin <span class="text-rose-500">*</span></span>
      <div class="mt-1 flex flex-wrap gap-5">
        <?php foreach (['Laki-Laki' => 'jenis_kelamin_laki', 'Perempuan' => 'jenis_kelamin_perempuan'] as $value => $id): ?>
          <label class="check-label" for="<?= $id ?>">
            <input type="radio" class="check" name="jenis_kelamin" id="<?= $id ?>"
                   value="<?= $value ?>" <?= $petugas['jenis_kelamin'] === $value ? 'checked' : '' ?> required />
            <?= $value ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="sm:col-span-2">
      <label for="profil_gambar" class="field-label">Foto Profil</label>
      <input type="file" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100
                     file:px-3 file:py-1.5 file:text-xs file:font-semibold
                     file:text-slate-700 hover:file:bg-slate-200"
             id="profil_gambar" name="profil_gambar" accept="image/*" />
      <?php
      // Foto lama bisa berformat lama (Assets/uploads/<nama asli>).
      // Kalau pola nama acak tidak cocok, urlFoto() mengembalikan null
      // dan kita pakai inisial.
      $fotoLamaUrl = Profil::urlFoto($petugas['profil_gambar'] ?? null, '../../');
      $fotoLamaLokal = $petugas['profil_gambar'] ?? '';
      $adaFotoLama = $fotoLamaUrl !== null
          || (is_file(__DIR__ . '/../../Assets/uploads/' . basename($fotoLamaLokal)));
      ?>
      <?php if ($adaFotoLama): ?>
        <?php if ($fotoLamaUrl): ?>
          <img src="<?= $e($fotoLamaUrl) ?>" alt="Foto Profil Saat Ini"
               class="mt-3 h-20 w-20 rounded-xl object-cover ring-1 ring-slate-200" />
        <?php else: ?>
          <img src="../../Assets/uploads/<?= $e(basename($fotoLamaLokal)) ?>" alt="Foto Profil Saat Ini"
               class="mt-3 h-20 w-20 rounded-xl object-cover ring-1 ring-slate-200" />
        <?php endif; ?>
        <p class="field-hint">Biarkan kosong bila tidak ingin mengganti foto.</p>
      <?php endif; ?>
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
