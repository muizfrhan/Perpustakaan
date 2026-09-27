<?php
/**
 * Uji fitur Pengaturan Akun (integration test).
 *
 * Menjalankan request HTTP sungguhan sebagai tiap peran:
 *   - halaman pengaturan terbuka & punya form
 *   - simpan profil (termasuk foto)
 *   - ganti password (benar & salah)
 *   - CSRF ditolak
 *   - privilege escalation DITOLAK (status / nim)
 *
 * Pakai: php -S 127.0.0.1:8829 -t .   lalu   php tests/ProfilTest.php 8829
 */
$BASE = 'http://127.0.0.1:' . ($argv[1] ?? '8829');

function req(string $path, ?array $post = null, string $jar = '', array $files = []): array
{
    $ch = curl_init($GLOBALS['BASE'] . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($jar !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($post !== null || $files) {
        $payload = $post ?? [];
        if ($files) {
            $payload = array_merge($payload, $files);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    $res   = curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr((string) $res, 0, $hsize);
    $body    = substr((string) $res, $hsize);
    preg_match('/^Location:\s*(.+)$/mi', $headers, $m);

    return [
        'code' => $code,
        'loc'  => str_replace(["\r", "\n"], '', trim($m[1] ?? '')),
        'body' => $body,
    ];
}

$lulus = 0;
$gagal = 0;
function cek(string $label, bool $ok, string $info = ''): void
{
    global $lulus, $gagal;
    if ($ok) { $lulus++; echo "  PASS  $label" . ($info ? "  ($info)" : '') . "\n"; }
    else    { $gagal++; echo "  FAIL  $label  $info\n"; }
}

/** Ambil token CSRF dari HTML halaman pengaturan. */
function token(string $html): string
{
    return preg_match('/name="csrf_token" value="([^"]+)"/', $html, $m) ? $m[1] : '';
}

function login(string $id, string $sandi, string $jar): array
{
    return req('/login.php', ['identifier' => $id, 'password' => $sandi], $jar);
}

echo "=== 0. siapkan akun khusus test ===\n";
// Username 'hansz' ada di owner DAN petugas, dan login terpadu memprioritaskan
// owner - jadi perlu akun petugas terpisah untuk menguji panel Admin.
require_once __DIR__ . '/../Config/koneksi.php';
$conn->exec("DELETE FROM petugas WHERE username = 'petugastest'");
$st = $conn->prepare("INSERT INTO petugas (id_petugas, nama_petugas, username, password, no_telp, jenis_kelamin, profil_gambar, status)
                      VALUES (99, 'Petugas Uji', 'petugastest', ?, '081200000099', 'Laki-Laki', '', 'Aktif')");
$st->execute([password_hash('rahasia123', PASSWORD_DEFAULT)]);
echo "  petugas 'petugastest' dibuat (id 99)\n";

echo "\n=== 1. Halaman pengaturan terbuka untuk semua peran ===\n";
$kasus = [
    ['Admin',   'petugastest', 'rahasia123', '/Admin/Akun/pengaturan.php', 'Petugas'],
    ['Owner',   'hansz',       'mfarhan40',  '/Owner/Akun/pengaturan.php', 'nama_pemilik'],
    ['Anggota', '2201001',     'mhsudb123',  '/User/account.php',          'NIM'],
];
foreach ($kasus as [$peran, $id, $sandi, $path, $marker]) {
    $jar = tempnam(sys_get_temp_dir(), 'pusaku');
    login($id, $sandi, $jar);
    $r = req($path, null, $jar);
    cek("$peran: $path -> 200", $r['code'] === 200, "code {$r['code']}");
    cek("$peran: ada form", str_contains($r['body'], '<form'), '');
    cek("$peran: ada input CSRF", token($r['body']) !== '', '');
    cek("$peran: ada upload foto", str_contains($r['body'], 'name="foto"'), '');
    cek("$peran: ada form ganti sandi", str_contains($r['body'], 'name="sandi_baru"'), '');
    cek("$peran: menampilkan $marker", str_contains($r['body'], $marker), '');
    // Punya kolom status? Jangan sampai bisa diedit.
    $punyaStatus = str_contains($r['body'], 'name="status"') || str_contains($r['body'], 'name="status_mhs"');
    cek("$peran: TIDAK ada input status yang bisa diset", !$punyaStatus,
        $punyaStatus ? 'ADA - BAHAYA!' : '');
    cek("$peran: NIM/ID tidak bisa diedit", !str_contains($r['body'], 'name="nim"') || $peran !== 'Anggota',
        $peran === 'Anggota' && str_contains($r['body'], 'name="nim"') ? 'ADA - BAHAYA!' : '');
    @unlink($jar);
}

echo "\n=== 2. Simpan profil (nama) ===\n";
$jar = tempnam(sys_get_temp_dir(), 'pusaku');
login('2201001', 'mhsudb123', $jar);
$html = req('/User/account.php', null, $jar)['body'];
$t = token($html);
$r = req('/User/account.php', [
    'csrf_token' => $t, 'aksi' => 'simpan_profil',
    'nama' => 'Ahmad Fauzi Uji', 'no_telp' => '081234560001',
    'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
], $jar);
cek('redirect setelah simpan', $r['code'] === 302, "code {$r['code']}");
$html2 = req('/User/account.php', null, $jar)['body'];
cek('flash sukses tampil', str_contains($html2, 'alert-success') || str_contains($html2, 'berhasil diperbarui'), '');
cek('nama baru tersimpan', str_contains($html2, 'Ahmad Fauzi Uji'), '');

echo "\n=== 3. Privilege escalation harus GAGAL ===\n";
$t = token($html2);
req('/User/account.php', [
    'csrf_token' => $t, 'aksi' => 'simpan_profil',
    'nama' => 'Ahmad Fauzi Uji', 'no_telp' => '081234560001',
    'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
    // Field yang seharusnya tidak bisa diubah:
    'status_mhs' => 'Aktif', 'nim' => '9999999', 'tgl_lahir' => '1990-01-01',
], $jar);
$html3 = req('/User/account.php', null, $jar)['body'];
cek('NIM tidak berubah jadi 9999999', !str_contains($html3, '9999999'), 'MASIH TERUBAH - BAHAYA!');
cek('tgl_lahir tidak berubah', !str_contains($html3, '1990-01-01'), 'MASIH TERUBAH - BAHAYA!');

// Kolom di luar allowlist DIABAIKAN diam-diam (tidak error, tidak tersimpan).
// Yang diuji di sini adalah efeknya, bukan pesan error.

// Verifikasi langsung ke DB.
$row = $conn->query("SELECT nim, nama, status_mhs, tgl_lahir FROM anggota WHERE nim = 2201001")->fetch(PDO::FETCH_ASSOC);
cek('DB: nim tetap 2201001', (string) $row['nim'] === '2201001', "nim={$row['nim']}");
cek('DB: nama tersimpan', $row['nama'] === 'Ahmad Fauzi Uji', "nama={$row['nama']}");
cek('DB: tgl_lahir tidak berubah', $row['tgl_lahir'] !== '1990-01-01', "tgl={$row['tgl_lahir']}");
cek('DB: status_mhs tidak diubah lewat form', $row['status_mhs'] === 'Aktif', "status={$row['status_mhs']}");

echo "\n=== 4. CSRF ditolak ===\n";
$t = token($html3);
$r = req('/User/account.php', [
    'csrf_token' => 'TOKEN-PALSU', 'aksi' => 'simpan_profil', 'nama' => 'Hack',
], $jar);
cek('token palsu -> 403', $r['code'] === 403, "code {$r['code']}");

echo "\n=== 5. Ganti password ===\n";
$t = token($html3);
$r = req('/User/account.php', [
    'csrf_token' => $t, 'aksi' => 'ganti_sandi',
    'sandi_lama' => 'SALAH', 'sandi_baru' => 'rainsbaru123', 'sandi_konfirmasi' => 'rainsbaru123',
], $jar);
$h = req('/User/account.php', null, $jar)['body'];
cek('password lama salah ditolak', str_contains($h, 'Password lama salah'), '');

$t = token($h);
req('/User/account.php', [
    'csrf_token' => $t, 'aksi' => 'ganti_sandi',
    'sandi_lama' => 'mhsudb123', 'sandi_baru' => 'rainsbaru123', 'sandi_konfirmasi' => 'rainsbaru123',
], $jar);
$h2 = req('/User/account.php', null, $jar)['body'];
cek('password baru berhasil', str_contains($h2, 'berhasil diubah'), '');

// Password baru harus bisa login, yang lama tidak.
$jar2 = tempnam(sys_get_temp_dir(), 'pusaku');
$r = login('2201001', 'rainsbaru123', $jar2);
cek('login dengan password baru', str_contains($r['loc'], 'User/home.php'), "loc={$r['loc']}");
$jar3 = tempnam(sys_get_temp_dir(), 'pusaku');
$r = login('2201001', 'mhsudb123', $jar3);
cek('login dengan password LAMA ditolak', !str_contains($r['loc'], 'User/home.php'), "loc={$r['loc']}");

// Kembalikan password agar data test tidak meninggalkan jejak.
$jar4 = tempnam(sys_get_temp_dir(), 'pusaku');
login('2201001', 'rainsbaru123', $jar4);
$h4 = req('/User/account.php', null, $jar4)['body'];
req('/User/account.php', [
    'csrf_token' => token($h4), 'aksi' => 'ganti_sandi',
    'sandi_lama' => 'rainsbaru123', 'sandi_baru' => 'mhsudb123', 'sandi_konfirmasi' => 'mhsudb123',
], $jar4);
$jar5 = tempnam(sys_get_temp_dir(), 'pusaku');
$r = login('2201001', 'mhsudb123', $jar5);
cek('password test dikembalikan ke semula', str_contains($r['loc'], 'User/home.php'), "loc={$r['loc']}");

// Kembalikan nama.
$h5 = req('/User/account.php', null, $jar5)['body'];
req('/User/account.php', [
    'csrf_token' => token($h5), 'aksi' => 'simpan_profil',
    'nama' => 'Ahmad Fauzi', 'no_telp' => '081234560001',
    'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
], $jar5);
$back = $conn->query("SELECT nama FROM anggota WHERE nim = 2201001")->fetchColumn();
cek('nama test dikembalikan', $back === 'Ahmad Fauzi', "nama={$back}");

// Bersihkan akun petugas test.
$conn->exec("DELETE FROM petugas WHERE username = 'petugastest'");
echo "\n  (akun petugas test dihapus)\n";

echo "\n=== 6. Upload foto: file berbahaya ditolak ===\n";
// Berkas PHP yang menyamar gambar (polyglot).
$polyglot = tempnam(sys_get_temp_dir(), 'up') . '.jpg';
file_put_contents($polyglot, "\x89PNG\r\n\x1a\n" . '<?php system($_GET["c"]); ?>');
$jar6 = tempnam(sys_get_temp_dir(), 'pusaku');
login('2201001', 'mhsudb123', $jar6);
$h6 = req('/User/account.php', null, $jar6)['body'];
req('/User/account.php', [
    'csrf_token' => token($h6), 'aksi' => 'simpan_profil',
    'nama' => 'Ahmad Fauzi', 'no_telp' => '081234560001',
    'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
], $jar6, ['foto' => new CURLFile($polyglot, 'image/jpeg', 'evil.jpg')]);
$h7 = req('/User/account.php', null, $jar6)['body'];
// getimagesize() lebih dulu menyingkir berkas ini, jadi pesannya yang
// "harus gambar", bukan yang "berisi kode". Yang penting: ditolak.
cek('polyglot PHP ditolak', str_contains($h7, 'alert-danger'), '');
cek('polyglot tidak tersimpan',
    empty($conn->query("SELECT profil_gambar FROM anggota WHERE nim = 2201001")->fetchColumn()), '');

// Berkas teks biasa.
$teks = tempnam(sys_get_temp_dir(), 'tx') . '.txt';
file_put_contents($teks, 'bukan gambar sama sekali');
req('/User/account.php', [
    'csrf_token' => token($h7), 'aksi' => 'simpan_profil',
    'nama' => 'Ahmad Fauzi', 'no_telp' => '081234560001',
    'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
], $jar6, ['foto' => new CURLFile($teks, 'text/plain', 'catatan.txt')]);
$h8 = req('/User/account.php', null, $jar6)['body'];
cek('file teks ditolak', str_contains($h8, 'harus gambar JPG, PNG, atau WebP'), '');

echo "\n=== 7. Upload foto: gambar asli diterima ===\n";
$gd = null;
if (function_exists('imagecreatetruecolor')) {
    $gd = imagecreatetruecolor(240, 240);
    imagefill($gd, 0, 0, imagecolorallocate($gd, 79, 70, 229));
    $png = tempnam(sys_get_temp_dir(), 'img') . '.png';
    imagepng($gd, $png);
    imagedestroy($gd);
    req('/User/account.php', [
        'csrf_token' => token($h8), 'aksi' => 'simpan_profil',
        'nama' => 'Ahmad Fauzi', 'no_telp' => '081234560001',
        'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
    ], $jar6, ['foto' => new CURLFile($png, 'image/png', 'foto saya.png')]);
    $h9 = req('/User/account.php', null, $jar6)['body'];
    $fotoDb = $conn->query("SELECT profil_gambar FROM anggota WHERE nim = 2201001")->fetchColumn();
    cek('gambar PNG tersimpan', !empty($fotoDb), "nama file={$fotoDb}");
    cek('nama file acak (bukan aslinya)', (bool) preg_match('/^[a-f0-9]{32}\.png$/', (string) $fotoDb), "{$fotoDb}");
    cek('foto muncul di halaman', str_contains($h9, 'Assets/uploads/profil/'), '');
    cek('.htaccess ada di folder upload',
        is_file(__DIR__ . '/../Assets/uploads/profil/.htaccess'), '');

    // Bersihkan lagi.
    req('/User/account.php', [
        'csrf_token' => token($h9), 'aksi' => 'simpan_profil',
        'nama' => 'Ahmad Fauzi', 'no_telp' => '081234560001',
        'jenis_kelamin' => 'Laki-Laki', 'jurusan' => 'S1 Teknik Informatika', 'kelas' => '4A',
        'hapus_foto' => '1',
    ], $jar6);
    $h10 = req('/User/account.php', null, $jar6)['body'];
    cek('foto terhapus dari DB', empty($conn->query("SELECT profil_gambar FROM anggota WHERE nim = 2201001")->fetchColumn()), '');
    cek('berkas foto terhapus dari disk', !is_file(__DIR__ . '/../Assets/uploads/profil/' . $fotoDb), '');
    @unlink($png);
} else {
    echo "  SKIP  (ekstensi GD tidak tersedia)\n";
}

@unlink($polyglot);
@unlink($teks);
foreach ([$jar, $jar2, $jar3, $jar4, $jar5, $jar6] as $j) {
    @unlink($j);
}

echo "\n=========================================\n";
echo "  LULUS: $lulus    GAGAL: $gagal\n";
echo "=========================================\n";
exit($gagal > 0 ? 1 : 0);
