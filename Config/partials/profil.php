<?php
/**
 * Halaman "Pengaturan Akun" - dipakai bersama oleh ketiga peran.
 *
 * include partial ini SELALUR dengan kontrak berikut diset BEFORE include:
 *
 *   $peranProfil      : 'owner' | 'petugas' | 'anggota'
 *   $urlPrefix  : '../../'  (relatif lokasi file pemanggil ke root project)
 *   $conn       : koneksi PDO
 *
 * Partial ini melakukan tiga hal:
 *   1. Menangani POST (simpan profil / ganti sandi / hapus foto), lalu
 *      me-redirect supaya tidak double-submit.
 *   2. Mengambil data profil.
 *   3. Merender UI.
 *
 * Sengaja TIDAK bisa mengubah `status` / `status_mhs`: kolom itu tidak
 * ada di allowlist App\Services\Profil, jadi walau ada POST manual
 * (`status=Aktif`) efeknya nol - mencegah eskalasi privilege.
 *
 * @var string     $peranProfil
 * @var string     $urlPrefix
 * @var PDO        $conn
 */

use App\Services\Profil;

if (!defined('BASE_PATH')) {
    // Dipanggil tanpa kernel (mis. tes) - muat otomatis.
    require_once __DIR__ . '/../bootstrap.php';
}

$def     = Profil::PERAN[$peranProfil];
$flash   = ambil_flash();
$error   = null;
$sukses  = null;

// ---------------------------------------------------------------------------
// 1. Tangani POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();          // tolak GET
    csrf_verify();           // tolak tanpa token valid

    $aksi = $_POST['aksi'] ?? '';

    switch ($aksi) {
        // -------------------------------------------------------------------
        case 'simpan_profil':
            $data = [];

            // Nama tampilan: kolom pertama sesuai peran.
            $kolomNama = match ($peranProfil) {
                'owner'   => 'nama_pemilik',
                'petugas' => 'nama_petugas',
                default   => 'nama',
            };
            $data[$kolomNama] = $_POST[$kolomNama] ?? '';

            foreach (['username', 'no_telp', 'jenis_kelamin', 'jurusan', 'kelas'] as $k) {
                if ($k === 'username' && !$def['adaUsername']) {
                    continue;   // anggota tidak punya username
                }
                if (array_key_exists($k, $_POST)) {
                    $data[$k] = $_POST[$k];
                }
            }

            // 1a. Foto: hapus bila diminta, kalau tidak coba upload baru.
            if (!empty($_POST['hapus_foto'])) {
                $lama = Profil::ambil($conn, $peranProfil)['profil_gambar'] ?? null;
                if (Profil::hapusFoto($lama)) {
                    $data['profil_gambar'] = null;
                }
            } elseif (!empty($_FILES['foto']['name'])) {
                $hasil = Profil::unggahFoto($_FILES['foto']);
                if ($hasil['ok']) {
                    // Hapus foto lama supaya folder tidak menumpuk sampah.
                    $lama = Profil::ambil($conn, $peranProfil)['profil_gambar'] ?? null;
                    if ($lama && $lama !== $hasil['nama']) {
                        Profil::hapusFoto($lama);
                    }
                    $data['profil_gambar'] = $hasil['nama'];
                } else {
                    flash('danger', $hasil['pesan']);
                    header('Location: ' . basename($_SERVER['PHP_SELF']));
                    exit;
                }
            }

            $hasil = Profil::simpan($conn, $peranProfil, $data);

            if ($hasil['ok'] && $def['adaUsername'] && !empty($data['username'])) {
                // Peringatan username ganda lintas tabel (login terpadu).
                $lintas = Profil::usernameLintasPeran($conn, $peranProfil, (string) $data['username']);
                if ($lintas) {
                    $hasil['pesan'] .= ' Catatan: username ini juga dipakai akun '
                        . implode(' dan ', $lintas)
                        . '. Karena login memakai satu form untuk semua peran,'
                        . ' username sebaiknya unik lintas tabel.';
                }
            }

            flash($hasil['ok'] ? 'success' : 'danger', $hasil['pesan']);
            header('Location: ' . basename($_SERVER['PHP_SELF']));
            exit;

        // -------------------------------------------------------------------
        case 'ganti_sandi':
            $hasil = Profil::gantiSandi(
                $conn,
                $peranProfil,
                (string) ($_POST['sandi_lama'] ?? ''),
                (string) ($_POST['sandi_baru'] ?? ''),
                (string) ($_POST['sandi_konfirmasi'] ?? '')
            );

            flash($hasil['ok'] ? 'success' : 'danger', $hasil['pesan']);
            header('Location: ' . basename($_SERVER['PHP_SELF']) . '#keamanan');
            exit;
    }
}

// ---------------------------------------------------------------------------
// 2. Ambil data
// ---------------------------------------------------------------------------
$profil = Profil::ambil($conn, $peranProfil);

if ($profil === null) {
    http_response_code(404);
    exit('Data akun tidak ditemukan.');
}

$urlFoto  = Profil::urlFoto($profil['profil_gambar'] ?? null, $urlPrefix);
$inisial  = strtoupper(mb_substr((string) ($profil['nama_pemilik'] ?? $profil['nama_petugas'] ?? $profil['nama'] ?? '?'), 0, 1));
$namaTampil = (string) ($profil['nama_pemilik'] ?? $profil['nama_petugas'] ?? $profil['nama'] ?? '');

/** Label status akun (read-only - tidak bisa diubah dari sini). */
$statusAkun = match ($peranProfil) {
    'petugas' => $profil['status'] ?? '',
    'anggota' => $profil['status_mhs'] ?? '',
    default   => 'Aktif',
};
?>

<!-- ================= HEADER HALAMAN ================= -->
<section class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-title">Pengaturan Akun</h1>
    <p class="text-muted mt-1">
      Perbarui foto, data diri, dan password Anda.
    </p>
  </div>

  <?php if ($statusAkun !== ''): ?>
    <span class="<?= $statusAkun === 'Aktif' ? 'badge-safe' : 'badge-empty' ?>">
      <span class="h-1.5 w-1.5 rounded-full <?= $statusAkun === 'Aktif' ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
      Akun <?= e($statusAkun) ?>
    </span>
  <?php endif; ?>
</section>

<?php if ($flash): ?>
  <div role="status" class="mt-5">
    <div class="<?= $flash['tipe'] === 'success' ? 'alert-success' : 'alert-danger' ?>">
      <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="<?= $flash['tipe'] === 'success'
                ? 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
                : 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z' ?>" />
      </svg>
      <p class="font-medium"><?= e($flash['pesan']) ?></p>
    </div>
  </div>
<?php endif; ?>

<div class="mt-6 grid items-start gap-6 xl:grid-cols-3">

  <!-- ================= FOTO PROFIL ================= -->
  <section class="card-base p-6 xl:col-span-1">
    <h2 class="text-section">Foto Profil</h2>
    <p class="text-muted mt-1">JPG, PNG, atau WebP. Maksimal 2&nbsp;MB.</p>

    <form method="POST" enctype="multipart/form-data" class="mt-5" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan_profil" />

      <!-- Kolom ini ikut terkirim supaya data lain tidak ikut terhapus -->
      <input type="hidden" name="<?= $peranProfil === 'owner' ? 'nama_pemilik' : ($peranProfil === 'petugas' ? 'nama_petugas' : 'nama') ?>"
             value="<?= e($namaTampil) ?>" />
      <?php if ($def['adaUsername']): ?>
        <input type="hidden" name="username" value="<?= e((string) ($profil['username'] ?? '')) ?>" />
      <?php endif; ?>
      <?php if (in_array('no_telp', $def['kolom'], true)): ?>
        <input type="hidden" name="no_telp" value="<?= e((string) ($profil['no_telp'] ?? '')) ?>" />
      <?php endif; ?>
      <?php if (in_array('jenis_kelamin', $def['kolom'], true)): ?>
        <input type="hidden" name="jenis_kelamin" value="<?= e((string) ($profil['jenis_kelamin'] ?? '')) ?>" />
      <?php endif; ?>

      <!-- Pratinjau -->
      <div class="flex flex-col items-center">
        <span class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl
                     bg-brand-50 text-4xl font-bold text-brand-700 ring-4 ring-white">
          <?php if ($urlFoto): ?>
            <img src="<?= e($urlFoto) ?>" alt="Foto profil" class="h-full w-full object-cover" />
          <?php else: ?>
            <?= e($inisial) ?>
          <?php endif; ?>
        </span>

        <label for="foto" class="mt-4 block w-full">
          <span class="sr-only">Pilih foto</span>
          <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"
                 class="field field-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100
                        file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700
                        hover:file:bg-slate-200" />
        </label>

        <button type="submit" class="btn-primary btn-sm mt-3 w-full">Simpan Perubahan</button>

        <?php if ($urlFoto): ?>
          <label class="mt-3 flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-500">
            <input type="checkbox" name="hapus_foto" value="1" class="check" />
            Hapus foto saat ini
          </label>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <!-- ================= DATA AKUN + KEAMANAN ================= -->
  <div class="space-y-6 xl:col-span-2">

    <!-- Data akun -->
    <section class="card-base p-6">
      <h2 class="text-section">Data Akun</h2>
      <p class="text-muted mt-1">Identitas ini muncul di halaman dan struk.</p>

      <form method="POST" class="mt-5 space-y-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simpan_profil" />

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label for="f-nama" class="field-label">
              <?= $peranProfil === 'owner' ? 'Nama Pemilik' : ($peranProfil === 'petugas' ? 'Nama Petugas' : 'Nama Lengkap') ?>
              <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="f-nama" class="field"
                   name="<?= $peranProfil === 'owner' ? 'nama_pemilik' : ($peranProfil === 'petugas' ? 'nama_petugas' : 'nama') ?>"
                   value="<?= e($namaTampil) ?>" required data-autofocus />
          </div>

          <?php if ($def['adaUsername']): ?>
            <div>
              <label for="f-username" class="field-label">Username <span class="text-rose-500">*</span></label>
              <input type="text" id="f-username" class="field" name="username" data-autofocus
                     value="<?= e((string) ($profil['username'] ?? '')) ?>" required
                     autocomplete="username" />
              <p class="field-hint">3-60 karakter: huruf, angka, titik, strip, underscore.</p>
            </div>
          <?php else: ?>
            <div>
              <span class="field-label">NIM</span>
              <input type="text" class="field cursor-not-allowed bg-slate-50 text-slate-500"
                     value="<?= e((string) $profil['nim']) ?>" readonly />
              <p class="field-hint">NIM adalah identitas login dan tidak dapat diubah.</p>
            </div>
          <?php endif; ?>

          <?php if (in_array('no_telp', $def['kolom'], true)): ?>
            <div>
              <label for="f-telp" class="field-label">No. Telepon <span class="text-rose-500">*</span></label>
              <input type="tel" id="f-telp" class="field" name="no_telp"
                     value="<?= e((string) ($profil['no_telp'] ?? '')) ?>" required
                     inputmode="numeric" placeholder="08xxxxxxxxxx" />
            </div>
          <?php endif; ?>

          <?php if (in_array('jenis_kelamin', $def['kolom'], true)): ?>
            <div>
              <span class="field-label">Jenis Kelamin <span class="text-rose-500">*</span></span>
              <div class="mt-1 grid grid-cols-2 gap-2.5">
                <?php foreach (Profil::JENIS_KELAMIN as $jk): ?>
                  <label class="check-label rounded-xl border border-slate-300 px-3.5 py-2.5
                                has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50"
                         for="jk-<?= $jk === 'Laki-Laki' ? 'l' : 'p' ?>">
                    <input type="radio" class="check" name="jenis_kelamin" id="jk-<?= $jk === 'Laki-Laki' ? 'l' : 'p' ?>"
                           value="<?= $jk ?>" <?= ($profil['jenis_kelamin'] ?? '') === $jk ? 'checked' : '' ?> required />
                    <?= $jk ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (in_array('jurusan', $def['kolom'], true)): ?>
            <div>
              <label for="f-jurusan" class="field-label">Jurusan <span class="text-rose-500">*</span></label>
              <select id="f-jurusan" class="field-select" name="jurusan" required>
                <option value="" disabled>Pilih jurusan</option>
                <?php foreach (Profil::JURUSAN as $j): ?>
                  <option value="<?= e($j) ?>" <?= ($profil['jurusan'] ?? '') === $j ? 'selected' : '' ?>><?= e($j) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <?php if (in_array('kelas', $def['kolom'], true)): ?>
            <div>
              <label for="f-kelas" class="field-label">Kelas <span class="text-rose-500">*</span></label>
              <input type="text" id="f-kelas" class="field" name="kelas"
                     value="<?= e((string) ($profil['kelas'] ?? '')) ?>" required placeholder="4A" />
            </div>
          <?php endif; ?>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-4">
          <button type="submit" class="btn-primary btn-sm">Simpan Data Akun</button>
        </div>
      </form>
    </section>

    <!-- Keamanan -->
    <section id="keamanan" class="card-base p-6">
      <h2 class="text-section">Keamanan</h2>
      <p class="text-muted mt-1">Ganti password yang dipakai untuk masuk.</p>

      <form method="POST" class="mt-5 space-y-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="ganti_sandi" />

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label for="sandi_lama" class="field-label">Password Saat Ini <span class="text-rose-500">*</span></label>
            <input type="password" id="sandi_lama" name="sandi_lama" class="field"
                   autocomplete="current-password" required />
          </div>

          <div>
            <label for="sandi_baru" class="field-label">Password Baru <span class="text-rose-500">*</span></label>
            <div class="relative">
              <input type="password" id="sandi_baru" name="sandi_baru" class="field pl-11 pr-11"
                     autocomplete="new-password" required minlength="8" />
              <button type="button" data-toggle-password="#sandi_baru" aria-label="Tampilkan sandi"
                      class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400
                             transition hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
            <p class="field-hint">Minimal <?= Profil::MIN_SANDI ?> karakter.</p>
          </div>

          <div>
            <label for="sandi_konfirmasi" class="field-label">Ulangi Password Baru <span class="text-rose-500">*</span></label>
            <input type="password" id="sandi_konfirmasi" name="sandi_konfirmasi" class="field"
                   autocomplete="new-password" required minlength="8" />
          </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-4">
          <button type="submit" class="btn-primary btn-sm">Ganti Password</button>
        </div>
      </form>
    </section>
  </div>
</div>
