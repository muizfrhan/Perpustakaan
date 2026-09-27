<?php
/**
 * Unit test logika denda - TANPA database.
 *
 * Jalankan:
 *   C:\xampp\php\php.exe tests\DendaServiceTest.php
 *
 * Test ini sengaja memakai aturan override supaya bisa menguji edge case
 * (grace period, cap, kondisi) tanpa bergantung pada isi tabel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../Config/Services/KondisiBuku.php';
require_once __DIR__ . '/../Config/Services/DendaResult.php';
require_once __DIR__ . '/../Config/Services/DendaService.php';

use App\Services\KondisiBuku;
use App\Services\DendaService;

$lulus  = 0;
$gagal  = 0;
$hasil  = [];

// PDO palsu: DendaService tidak boleh menyentuh database kalau ada override.
$pdoPalsu = new class extends PDO {
    public function __construct() {}
    public function query(string $q, ?int $fetchMode = null, ...$a): PDOStatement|false
    {
        throw new RuntimeException('Unit test tidak boleh menyentuh database.');
    }
};

$aturan = [
    'TERLAMBAT' => ['nominal' => 5000, 'grace_hari' => 0, 'hari_maks' => 60],
    'RUSAK'     => ['nominal' => 20000, 'grace_hari' => 0, 'hari_maks' => null],
    'HILANG'    => ['nominal' => 50000, 'grace_hari' => 0, 'hari_maks' => null],
];

$denda = new DendaService($pdoPalsu, $aturan);

/** @param callable $fn Closure yang mengembalikan nilai aktual */
function cek(string $nama, mixed $harapan, callable $fn): void
{
    global $lulus, $gagal, $hasil;

    try {
        $aktual = $fn();
    } catch (Throwable $ex) {
        $aktual = 'EXCEPTION: ' . $ex->getMessage();
    }

    if ($aktual === $harapan) {
        $lulus++;
        $hasil[] = "  [LULUS] $nama";
    } else {
        $gagal++;
        $hasil[] = sprintf(
            "  [GAGAL] %s\n           diharapkan: %s\n           aktual    : %s",
            $nama,
            var_export($harapan, true),
            var_export($aktual, true)
        );
    }
}

echo "== TEST PERHITUNGAN DENDA ==\n";

// ---------------------------------------------------------------------------
echo "\n[1] Tepat waktu\n";
// ---------------------------------------------------------------------------
cek(
    'Tepat waktu: Rp0',
    0,
    fn() => $denda->hitung('2026-09-27', '2026-09-27', KondisiBuku::Bagus)->total()
);

cek(
    'Dikembalikan 1 HARI SEBELUM tenggat: Rp0',
    0,
    fn() => $denda->hitung('2026-09-27', '2026-09-26', KondisiBuku::Bagus)->total()
);

// ---------------------------------------------------------------------------
echo "\n[2] Terlambat - kasus dasar\n";
// ---------------------------------------------------------------------------
cek(
    'Terlambat 1 hari: Rp5.000',
    5000,
    fn() => $denda->hitung('2026-09-27', '2026-09-28', KondisiBuku::Bagus)->total()
);

cek(
    'Terlambat 5 hari: Rp25.000',
    25000,
    fn() => $denda->hitung('2026-09-27', '2026-10-02', KondisiBuku::Bagus)->total()
);

// ---------------------------------------------------------------------------
echo "\n[3] REGRESI - bug lama: hari pecahan\n";
// Logika lama: (strtotime(kembali) - strtotime(estimasi)) / 86400
//   estimasi 2026-09-25 10:00:00, kembali 2026-09-27 00:00:00
//   => selisih 38 jam = 1.5833 hari => 1.5833 x 5000 = Rp7.916,66  (SALAH)
// Logika baru: hitung hari KALENDER, jam diabaikan
//   => 2026-09-25 -> 2026-09-27 = 2 hari => 2 x 5000 = Rp10.000  (BENAR)
// ---------------------------------------------------------------------------
cek(
    'Jam 00:00 tidak lagi menggeser hasil (2 hari, bukan 1.5833)',
    10000,
    fn() => $denda->hitung('2026-09-25 10:00:00', '2026-09-27 00:00:00', KondisiBuku::Bagus)->total()
);

cek(
    'Batas 23:59 tetap dihitung 1 hari',
    5000,
    fn() => $denda->hitung('2026-09-27 23:59:00', '2026-09-28 00:01:00', KondisiBuku::Bagus)->total()
);

cek(
    'Hari yang sama dengan jam berbeda = 0 (tidak negatif, tidak 1)',
    0,
    fn() => $denda->hitung('2026-09-27 23:00:00', '2026-09-27 01:00:00', KondisiBuku::Bagus)->total()
);

// ---------------------------------------------------------------------------
echo "\n[4] Denda kondisi\n";
// ---------------------------------------------------------------------------
cek(
    'Rusak, tepat waktu: Rp20.000',
    20000,
    fn() => $denda->hitung('2026-09-27', '2026-09-27', KondisiBuku::Rusak)->total()
);

cek(
    'Rusak + terlambat 2 hari: Rp30.000',
    30000,
    fn() => $denda->hitung('2026-09-27', '2026-09-29', KondisiBuku::Rusak)->total()
);

cek(
    'Hilang, terlambat 10 hari: Rp100.000',
    100000,
    fn() => $denda->hitung('2026-09-27', '2026-10-07', KondisiBuku::Hilang)->total()
);

cek(
    'Normalisasi input lowercase "rusak" dari form lama',
    20000,
    fn() => $denda->hitung('2026-09-27', '2026-09-27', KondisiBuku::fromInput('rusak'))->total()
);

cek(
    'Normalisasi input "RUSAK" (huruf besar)',
    20000,
    fn() => $denda->hitung('2026-09-27', '2026-09-27', KondisiBuku::fromInput('RUSAK'))->total()
);

cek(
    'Input tidak dikenal -> dianggap Bagus (Rp0)',
    0,
    fn() => $denda->hitung('2026-09-27', '2026-09-27', KondisiBuku::fromInput('ngawur'))->total()
);

// ---------------------------------------------------------------------------
echo "\n[5] Cap atas (hari_maks = 60)\n";
// Tanpa cap, 1 tahun = 365 x 5000 = Rp1.825.000 (tidak masuk akal).
// Dengan cap 60 => 300.000
// ---------------------------------------------------------------------------
cek(
    'Terlambat 100 hari, dibatasi 60 hari: Rp300.000',
    300000,
    fn() => $denda->hitung('2026-09-27', '2027-01-05', KondisiBuku::Bagus)->total()
);

cek(
    'Terlambat 365 hari, dibatasi 60 hari: Rp300.000',
    300000,
    fn() => $denda->hitung('2026-09-27', '2027-09-27', KondisiBuku::Bagus)->total()
);

// ---------------------------------------------------------------------------
echo "\n[6] Grace period\n";
// ---------------------------------------------------------------------------
$bertenggang = new DendaService($pdoPalsu, [
    'TERLAMBAT' => ['nominal' => 5000, 'grace_hari' => 2, 'hari_maks' => 60],
    'RUSAK'     => ['nominal' => 20000, 'grace_hari' => 0, 'hari_maks' => null],
    'HILANG'    => ['nominal' => 50000, 'grace_hari' => 0, 'hari_maks' => null],
]);

cek(
    'Grace 2 hari: terlambat 2 hari = Rp0',
    0,
    fn() => $bertenggang->hitung('2026-09-27', '2026-09-29', KondisiBuku::Bagus)->total()
);

cek(
    'Grace 2 hari: terlambat 5 hari = 3 hari x 5000 = Rp15.000',
    15000,
    fn() => $bertenggang->hitung('2026-09-27', '2026-10-02', KondisiBuku::Bagus)->total()
);

// ---------------------------------------------------------------------------
echo "\n[7] sisaHari()\n";
// ---------------------------------------------------------------------------
cek(
    'Tenggat 5 hari lagi -> sisa 5',
    5,
    fn() => $denda->sisaHari('2026-10-02', '2026-09-27')
);

cek(
    'Sudah terlambat 3 hari -> sisa -3',
    -3,
    fn() => $denda->sisaHari('2026-09-24', '2026-09-27')
);

cek(
    'Hari yang sama -> sisa 0',
    0,
    fn() => $denda->sisaHari('2026-09-27', '2026-09-27')
);

// ---------------------------------------------------------------------------
echo "\n[8] Proyeksi (buku belum dikembalikan)\n";
// ---------------------------------------------------------------------------
cek(
    'Proyeksi hari ini dari estimasi 3 hari lalu = Rp15.000',
    15000,
    fn() => $denda->proyeksi(date('Y-m-d', strtotime('-3 days')))->total()
);

// ---------------------------------------------------------------------------
echo "\n[9] Format & audit\n";
// ---------------------------------------------------------------------------
cek(
    'Format rupiah: Rp25.000',
    'Rp25.000',
    fn() => $denda->hitung('2026-09-27', '2026-10-02', KondisiBuku::Bagus)->formatRupiah()
);

cek(
    'Total selalu integer (tidak pernah float)',
    true,
    fn() => is_int($denda->hitung('2026-09-27', '2026-10-02', KondisiBuku::Rusak)->total())
);

cek(
    'dasar_hitung memuat rincian untuk audit',
    true,
    fn() => str_contains(
        $denda->hitung('2026-09-27', '2026-10-02', KondisiBuku::Rusak)->dasarHitung,
        'Buku rusak'
    )
);

cek(
    'dasar_hitung mencatat grace & cap yang berlaku',
    true,
    fn() => str_contains(
        $bertenggang->hitung('2026-09-27', '2026-10-02', KondisiBuku::Bagus)->dasarHitung,
        'grace 2 hari'
    )
);

cek(
    'dasar_hitung mencatat pembatasan maximum hari',
    true,
    fn() => str_contains(
        $denda->hitung('2026-09-27', '2027-01-05', KondisiBuku::Bagus)->dasarHitung,
        'dibatasi maksimum 60 hari'
    )
);

cek(
    'Tanggal tidak valid ditolak (tidak diam-diam 0)',
    'EXCEPTION: Format tanggal tidak dikenali: abc',
    fn() => $denda->hitung('abc', '2026-09-27', KondisiBuku::Bagus)->total()
);

// ---------------------------------------------------------------------------
// Ringkasan
// ---------------------------------------------------------------------------
echo "\n" . str_repeat('-', 60) . "\n";
foreach ($hasil as $baris) {
    echo $baris . "\n";
}
echo str_repeat('-', 60) . "\n";
printf("TOTAL: %d lulus, %d gagal\n", $lulus, $gagal);

exit($gagal > 0 ? 1 : 0);
