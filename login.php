<?php
/**
 * Login terpadu - SATU form untuk semua peran.
 *
 * Tidak ada lagi pemilihan role. Pengguna cukup mengetik username (atau
 * NIM untuk anggota) + password; sistem memeriksa tabel owner, petugas,
 * lalu anggota, dan mengarahkan ke dashboard yang sesuai.
 *
 * Pesan error sengaja dibuat generik agar tidak membocorkan apakah
 * sebuah username/NIM benar-benar terdaftar.
 */
require_once __DIR__ . '/Config/bootstrap.php';
require_once __DIR__ . '/Config/koneksi.php';

use App\Services\Auth;
use App\Services\IngatLogin;

$error = '';

/** Dashboard tujuan bila user ternyata sudah punya sesi aktif. */
$peranSesi = null;
if (!empty($_SESSION['id_owner'])) {
    $peranSesi = Auth::ROLE_OWNER;
} elseif (!empty($_SESSION['id_petugas'])) {
    $peranSesi = Auth::ROLE_ADMIN;
} elseif (!empty($_SESSION['nim'])) {
    $peranSesi = Auth::ROLE_USER;
}

if ($peranSesi !== null) {
    header('Location: ' . Auth::REDIRECT[$peranSesi]);
    exit;
}

// "Ingat Saya": belum punya sesi, tapi cookie token masih ada -> langsung
// lanjutkan. Ini yang membuat pengguna tidak perlu mengetik ulang.
if (IngatLogin::ada()) {
    try {
        $akun = IngatLogin::cobaLanjutkan($conn);

        if ($akun !== null) {
            Auth::loginSession($akun);
            header('Location: ' . Auth::REDIRECT[$akun['role']]);
            exit;
        }
    } catch (Throwable $e) {
        // Token tidak bisa dibaca / tabel belum ada. Fallback ke form login.
        error_log('Ingat login gagal: ' . $e->getMessage());
        IngatLogin::lupakanCookie();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = (string) ($_POST['identifier'] ?? '');
    $password   = (string) ($_POST['password'] ?? '');

    // Permintaan dari Assets/js/login.js. Balas JSON supaya tombol bisa
    // menampilkan spinner dan salam "selamat datang" sebelum browser
    // sempat berpindah halaman. Tanpa flag ini alur POST biasa tetap jalan.
    $mauJson = ($_POST['ajax'] ?? '') === '1';

    try {
        $hasil = Auth::attempt($conn, $identifier, $password);

        if ($hasil !== null) {
            Auth::loginSession($hasil);
            $tujuan = Auth::REDIRECT[$hasil['role']];

            // Centang "Ingat saya" -> terbitkan token 30 hari.
            // Tidak dicentang -> cabut token lama, karena memang sedang
            // tidak ingin diingat.
            try {
                if (!empty($_POST['ingat'])) {
                    IngatLogin::daftarkan($conn, $hasil);
                } else {
                    IngatLogin::hapus($conn, $hasil['role'], (int) $hasil['id']);
                }
            } catch (Throwable $e) {
                // Token bukan hal kritis: login tetap berhasil tanpa fitur ingat.
                error_log('Gagal menyimpan token ingat: ' . $e->getMessage());
            }

            if ($mauJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok'       => true,
                    // Login.php ada di root project, jadi tujuannya
                    // perlu prefiks ./ agar tidak salah relatif.
                    'redirect' => './' . $tujuan,
                    'nama'     => $hasil['nama'],
                    'peran'    => Auth::LABEL[$hasil['role']],
                    'ingat'    => !empty($_POST['ingat']),
                ]);
                exit;
            }

            header('Location: ' . $tujuan);
            exit;
        }

        $error = 'Username/NIM atau password salah.';
    } catch (Throwable $e) {
        $error = 'Terjadi kesalahan saat memproses. Silakan coba lagi.';
    }

    if ($mauJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'pesan' => $error]);
        exit;
    }
}

// Angka untuk panel kiri.
$statBuku    = (int) $conn->query('SELECT COUNT(*) FROM buku')->fetchColumn();
$statAnggota = (int) $conn->query('SELECT COUNT(*) FROM anggota')->fetchColumn();
$statPetugas = (int) $conn->query("SELECT COUNT(*) FROM petugas WHERE status = 'Aktif'")->fetchColumn();

$peranLabel = 'Pengguna';
$heading    = 'Satu pintu masuk untuk semua peran.';
$subheading = 'Masuk dengan username atau NIM. Sistem akan mengenali hak akses Anda secara otomatis.';
$accent     = 'brand';
$judulForm  = 'Masuk ke Pusaku';
$subForm    = 'Gunakan akun yang terdaftar - petugas, owner, atau anggota.';
$judulDoc   = 'Login - Pusaku';
$urlPrefix  = './';

// Sengaja dikosongkan: tidak ada pemilih peran lagi.
$portalLain = [];

require __DIR__ . '/Config/layouts/auth_start.php';
?>

        <div>
          <label for="identifier" class="field-label">Username atau NIM</label>
          <div class="relative">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                 fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <input type="text" name="identifier" id="identifier" autocomplete="username" required
                   data-autofocus class="field pl-11" placeholder="Username atau NIM" />
          </div>
        </div>

        <div>
          <label for="password" class="field-label">Password</label>
          <div class="relative">
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                 fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <input type="password" name="password" id="password" autocomplete="current-password" required
                   class="field pl-11 pr-11" placeholder="Masukkan password" />
            <button type="button" data-toggle-password="#password" aria-label="Tampilkan sandi"
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

        <!-- Ingat saya: centang => token 30 hari, tidak perlu login ulang. -->
        <label class="check-label" for="ingat">
          <input type="checkbox" name="ingat" id="ingat" value="1" class="check accent-brand-600" checked />
          <span>Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" data-login-submit
                class="btn <?= $tombolSubmit ?? 'btn-primary' ?> w-full py-3">
          Masuk
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
          </svg>
        </button>

        <p class="text-center text-xs leading-relaxed text-slate-400">
          Belum punya akun? Hubungi petugas perpustakaan.
        </p>

<script src="<?= $urlPrefix ?>Assets/js/login.js" defer></script>

<?php require __DIR__ . '/Config/layouts/auth_end.php'; ?>
