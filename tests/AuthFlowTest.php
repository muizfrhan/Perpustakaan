<?php
/**
 * Uji alur autentikasi (integration test).
 *
 * Menguji request HTTP sungguhan: login terpadu, penolakan kredensial,
 * link logout di sidebar, dan URL lama yang harus tetap redirect.
 *
 * CARA MENJALANKAN (butuh PHP built-in server + database MySQL `pusaku`):
 *
 *   php -S 127.0.0.1:8862 -t .
 *   php tests/AuthFlowTest.php 8862
 *
 * Exit code 0 = semua lulus.
 *
 * Test ini dulu menemukan 2 bug: link logout sidebar memakai '../logout.php'
 * (kurang satu level sehingga 404 di /Admin/Buku/ dkk), dan halaman Owner
 * yang masih mengarahkan ke '../Layouts/login.php'.
 */
$BASE = 'http://127.0.0.1:' . ($argv[1] ?? '8862');

function req(string $path, ?array $post = null, string $cookieJar = ''): array
{
    $ch = curl_init($GLOBALS['BASE'] . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,   // jangan ikuti redirect
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    if ($cookieJar !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($res, 0, $hsize);
    $body    = substr($res, $hsize);
    preg_match('/^Location:\s*(.+)$/mi', $headers, $m);

    return ['code' => $code, 'loc' => str_replace(["\r", "\n"], '', trim($m[1] ?? '')), 'body' => $body];
}

/** Normalisasi path: buang './' dan resolve '../' supaya sama dengan browser. */
function norm(string $p): string
{
    $abs = str_starts_with($p, '/');
    $out = [];
    foreach (explode('/', str_replace('\\', '/', $p)) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($out); continue; }
        $out[] = $seg;
    }
    return ($abs ? '/' : '') . implode('/', $out);
}

/** Ikuti rantai redirect sampaiNon-redirect, kembalikan path akhir. */
function reqIkuti(string $path, ?array $post = null, string $jar = ''): array
{
    $hops = 0;
    $awal = null;
    while ($hops < 6) {
        $r = req($path, $post, $jar);
        if ($awal === null) $awal = $r['code'];
        if ($r['code'] !== 302 || $r['loc'] === '') break;
        // Resolve Location relatif terhadap path saat ini, selalu jadi absolute.
        $target = $r['loc'];
        if ($target[0] !== '/') {
            $target = norm(dirname($path) . '/' . $target);
        }
        if ($target[0] !== '/') {
            $target = '/' . $target;
        }
        $path = $target;
        $post = null; // hanya POST pertama
        $hops++;
    }
    $r['awal'] = $awal;
    $r['akhir'] = $path;
    return $r;
}

$lulus = 0; $gagal = 0;
function cek(string $label, bool $ok, string $info = ''): void
{
    global $lulus, $gagal;
    if ($ok) { $lulus++; echo "  PASS  $label" . ($info ? "  ($info)" : '') . "\n"; }
    else    { $gagal++; echo "  FAIL  $label  $info\n"; }
}

echo "=== 1. Halaman login unified ===\n";
$r = req('/login.php');
cek('GET /login.php -> 200', $r['code'] === 200, "code {$r['code']}");
cek('ada form identifier', str_contains($r['body'], 'name="identifier"'));
cek('ada form password', str_contains($r['body'], 'name="password"'));
cek('TIDAK ada pemilih role', !str_contains($r['body'], 'atau masuk sebagai'),
    str_contains($r['body'], 'atau masuk sebagai') ? 'masih ada!' : '');
cek('TIDAK ada link Admin/Owner/Anggota sbg portal',
    !str_contains($r['body'], '../../Admin/Layouts/login.php')
    && !str_contains($r['body'], '../../Owner/Layouts/login.php'));

echo "\n=== 2. Login tiap peran (302 = benar) ===\n";
$r = req('/login.php', ['identifier' => '2201001', 'password' => 'mhsudb123']);
cek('anggota 2201001 -> User/home.php', $r['code'] === 302 && str_contains($r['loc'], 'User/home.php'),
    "{$r['code']} -> {$r['loc']}");

$r = req('/login.php', ['identifier' => '2201002', 'password' => 'mhsudb123']);
cek('anggota 2201002 -> User/home.php', $r['code'] === 302 && str_contains($r['loc'], 'User/home.php'),
    "{$r['code']} -> {$r['loc']}");

$r = req('/login.php', ['identifier' => 'hansz', 'password' => 'mfarhan40']);
cek('hansz -> dashboard (tabrakan owner+petugas)', $r['code'] === 302,
    "{$r['code']} -> {$r['loc']}");

echo "\n=== 3. Login ditolak ===\n";
foreach ([
    ['password salah', ['identifier' => '2201001', 'password' => 'salah']],
    ['username tak ada', ['identifier' => 'nobody', 'password' => 'x']],
    ['identifier kosong', ['identifier' => '', 'password' => '']],
    ['password kosong', ['identifier' => '2201001', 'password' => '']],
] as [$nama, $post]) {
    $r = req('/login.php', $post);
    cek("$nama ditolak (200 + pesan error)",
        $r['code'] === 200 && str_contains($r['body'], 'Username/NIM atau password salah'),
        "code {$r['code']}");
}

echo "\n=== 4. Halaman terlindungi ===\n";
$jar = tempnam(sys_get_temp_dir(), 'pusaku');
$r = req('/Owner/Dashboard/dashboard.php', null, $jar);
cek('dashboard Owner tanpa sesi -> 302 ke login', $r['code'] === 302 && str_contains($r['loc'], 'login.php'),
    "{$r['code']} -> {$r['loc']}");

echo "\n=== 5. Link logout di sidebar (tidak boleh 404) ===\n";
// Login sebagai owner (hansz) supaya halaman ber-guard bisa dirender.
$jar = tempnam(sys_get_temp_dir(), 'pusaku');
$rl  = req('/login.php', ['identifier' => 'hansz', 'password' => 'mfarhan40'], $jar);
cek('login hansz -> Owner dashboard', str_contains($rl['loc'], 'Owner/Dashboard'),
    "{$rl['code']} -> {$rl['loc']}");

// Semua halaman ber-guard yang memakai shell sidebar.
$halamanShell = [
    '/Owner/Dashboard/dashboard.php',
    '/Owner/Admin/admin.php',
    '/Owner/Anggota/anggota.php',
    '/Owner/Buku/buku.php',
    '/Owner/Peminjaman/peminjaman.php',
    '/Owner/Pengembalian/pengembalian.php',  // URL yang dilaporkan TOKEN
];
$totalLink = 0;
$logoutUrls = [];

// Tahap 1: kumpulkan link logout dari setiap halaman (sesi harus hidup).
// Kalau link-nya langsung dibuka di dalam loop, session akan destroy di
// iterasi pertama dan semua halaman berikutnya hanya dapat 302 ke login.
foreach ($halamanShell as $u) {
    $r = req($u, null, $jar);
    preg_match_all('/href="([^"]*logout[^"]*)"/i', $r['body'], $m);
    if (empty($m[1])) {
        cek("$u punya link logout", false, "tidak ditemukan di HTML (code={$r['code']})");
        continue;
    }
    foreach ($m[1] as $href) {
        $totalLink++;
        $logoutUrls[] = $href[0] === '/'
            ? norm($href)
            : norm(rtrim(dirname($u), '/') . '/' . $href);
    }
}

// Tahap 2: pastikan setiap link logout benar-benar ada (tidak 404).
foreach (array_unique($logoutUrls) as $resolved) {
    $rr = req($resolved, null, $jar);
    cek("logout tidak 404: $resolved", $rr['code'] !== 404,
        "code={$rr['code']} loc={$rr['loc']}");
}

cek('semua halaman shell punya link logout', $totalLink >= count($halamanShell),
    "$totalLink link dari " . count($halamanShell) . " halaman");
@unlink($jar);



echo "\n=== 6. Semua path logout lama (ikut rantai redirect) ===\n";
foreach ([
    '/logout.php',
    '/Admin/Buku/logout.php',
    '/Admin/Dashboard/logout.php',
    '/Admin/Layouts/logout.php',
    '/Owner/Dashboard/logout.php',
    '/Owner/Layouts/logout.php',
] as $u) {
    $r = reqIkuti($u);
    cek("$u -> landing di login.php", str_contains($r['akhir'], 'login.php') && $r['code'] === 200,
        "akhir: {$r['akhir']} code={$r['code']}");
}

echo "\n=== 7. URL login lama -> redirect ===\n";
foreach (['/Admin/Layouts/login.php', '/Owner/Layouts/login.php', '/User/login.php',
          '/index.php', '/Owner/index.php'] as $u) {
    $r = req($u);
    cek("$u -> 302", $r['code'] === 302 && str_contains($r['loc'], 'login.php'),
        "{$r['code']} -> {$r['loc']}");
}

echo "\n=== 8. Halaman terlindungi -> login unified ===\n";
foreach ([
    '/Owner/Dashboard/dashboard.php',
    '/Admin/Dashboard/dashboard.php',
    '/User/dashboard.php',
    '/User/home.php',
    '/User/account.php',
    '/User/history.php',
] as $u) {
    $r = req($u);
    cek("$u -> 302 ke login.php", $r['code'] === 302 && str_contains($r['loc'], 'login.php'),
        "{$r['code']} -> {$r['loc']}");
}

echo "\n=========================================\n";
echo "  LULUS: $lulus    GAGAL: $gagal\n";
echo "=========================================\n";
exit($gagal > 0 ? 1 : 0);
