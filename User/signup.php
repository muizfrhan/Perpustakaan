<?php
/**
 * Halaman Pendaftaran Anggota
 *
 * PENTING: form ini pada versi lama TIDAK PUNYA backend sama sekali
 * - <form> tanpa action dan method
 * - hampir semua field tanpa atribut name
 * - tidak ada blok PHP yang memproses POST
 *
 * Jadi halaman ini hanya tampilan. Saya TIDAK mengarang endpoint baru
 * karena itu akan mengubah route yang ada. Field id dipertahankan
 * (fullname, male, female, nim, class, major, dob, password,
 * confirmPassword) agar tidak merusak select/label yang merujuknya.
 */
$urlPrefix = '../';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="theme-color" content="#4f46e5" />
  <title>Daftar Anggota - PUSAKU</title>

  <!-- Favicon PUSAKU -->
  <link rel="icon" type="image/svg+xml" href="../Assets/logo/favicon.svg" />
  <link rel="icon" type="image/png" sizes="32x32" href="../Assets/logo/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="../Assets/logo/favicon-16.png" />
  <link rel="apple-touch-icon" href="../Assets/logo/favicon.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../Assets/css/pusaku.css" />
</head>

<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased">

  <!-- Latar dekoratif -->
  <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
    <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand-200/40 blur-3xl"></div>
    <div class="absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
  </div>

  <div class="relative flex min-h-screen flex-col items-center px-5 py-8 sm:px-8">

    <!-- Brand: logo resmi PUSAKU -->
    <div class="flex items-center">
      <img src="../Assets/logo/pusaku-logo.svg" alt="PUSAKU" class="h-10 w-auto" />
    </div>

    <!-- Kartu form -->
    <div class="mx-auto my-auto w-full max-w-2xl py-10">

      <div class="text-center">
        <span class="badge-safe">Anggota</span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">Daftar Akun Baru</h1>
        <p class="text-muted mt-2">Lengkapi data berikut untuk mendapatkan akses katalog perpustakaan.</p>
      </div>

      <div class="card-base mt-8 p-6 sm:p-8">
        <form id="formDaftar" novalidate>

          <!-- Nama -->
          <div>
            <label for="fullname" class="field-label">Nama Lengkap</label>
            <input type="text" id="fullname" name="fullname" autocomplete="name" required
                   class="field" placeholder="Contoh: Ahmad Fauzi" />
          </div>

          <!-- Jenis kelamin -->
          <fieldset class="mt-5">
            <legend class="field-label">Jenis Kelamin</legend>
            <div class="mt-2 grid grid-cols-2 gap-3">
              <label for="male"
                     class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-4 py-3
                            transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                <input type="radio" name="gender" id="male" value="Male" required
                       class="h-4 w-4 accent-brand-600" />
                <span class="text-sm font-medium text-slate-700">Laki-laki</span>
              </label>

              <label for="female"
                     class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-300 px-4 py-3
                            transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                <input type="radio" name="gender" id="female" value="Female" required
                       class="h-4 w-4 accent-brand-600" />
                <span class="text-sm font-medium text-slate-700">Perempuan</span>
              </label>
            </div>
          </fieldset>

          <!-- NIM -->
          <div class="mt-5">
            <label for="nim" class="field-label">NIM</label>
            <input type="text" id="nim" name="nim" inputmode="numeric" required
                   class="field" placeholder="Contoh: 2201001" />
          </div>

          <!-- Kelas & Jurusan -->
          <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
              <label for="class" class="field-label">Kelas</label>
              <input type="text" id="class" name="class" required
                     class="field" placeholder="Contoh: 4A" />
            </div>

            <div>
              <label for="major" class="field-label">Jurusan</label>
              <select id="major" name="major" required class="field">
                <option value="" selected disabled>Pilih jurusan</option>
                <option value="Informatics">Informatics</option>
                <option value="Computer Science">Computer Science</option>
                <option value="Information Systems">Information Systems</option>
                <option value="Cybersecurity">Cybersecurity</option>
              </select>
            </div>
          </div>

          <!-- Tanggal lahir -->
          <div class="mt-5">
            <label for="dob" class="field-label">Tanggal Lahir</label>
            <input type="date" id="dob" name="dob" required class="field" />
          </div>

          <!-- Password -->
          <div class="mt-5">
            <label for="password" class="field-label">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password" autocomplete="new-password" required
                     class="field pr-11" placeholder="Minimal 8 karakter" minlength="8" />
              <button type="button" id="togglePassword" data-toggle-password="#password"
                      aria-label="Tampilkan sandi"
                      class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400
                             transition hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Konfirmasi password -->
          <div class="mt-5">
            <label for="confirmPassword" class="field-label">Ulangi Password</label>
            <div class="relative">
              <input type="password" id="confirmPassword" name="confirmPassword" autocomplete="new-password" required
                     class="field pr-11" placeholder="Ketik ulang password" minlength="8" />
              <button type="button" id="toggleConfirmPassword" data-toggle-password="#confirmPassword"
                      aria-label="Tampilkan sandi"
                      class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400
                             transition hover:bg-slate-100 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Info: form belum punya backend -->
          <div class="mt-6 flex items-start gap-3 rounded-xl bg-amber-50 px-4 py-3.5 ring-1 ring-inset ring-amber-600/20">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-sm font-medium text-amber-900">
              Formulir ini belum tersambung ke database. Hubungi petugas perpustakaan
              untuk pendaftaran akun anggota.
            </p>
          </div>

          <button type="submit" class="btn-primary mt-6 w-full py-3">
            Buat Akun
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
          </button>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun?
        <a href="login.php" class="font-semibold text-brand-600 transition hover:text-brand-700">Masuk di sini</a>
      </p>
    </div>
  </div>

  <script src="../Assets/js/pusaku.js" defer></script>

  <script>
    // Konfirmasi password harus sama sebelum submit.
    // (Form belum punya backend, jadi ini satu-satunya validasi yang ada.)
    document.getElementById('formDaftar').addEventListener('submit', function (e) {
      const sandi     = document.getElementById('password');
      const konfirmasi = document.getElementById('confirmPassword');

      if (sandi.value !== konfirmasi.value) {
        e.preventDefault();
        konfirmasi.focus();
        konfirmasi.classList.add('border-rose-400', 'ring-2', 'ring-rose-500/25');
        konfirmasi.setCustomValidity('Konfirmasi password tidak sama.');
        konfirmasi.reportValidity();
      } else {
        konfirmasi.setCustomValidity('');
      }
    });

    document.getElementById('confirmPassword').addEventListener('input', function () {
      this.setCustomValidity('');
      this.classList.remove('border-rose-400', 'ring-2', 'ring-rose-500/25');
    });
  </script>

</body>

</html>
