<?php
/**
 * Cron runner untuk pengingat tenggat.
 *
 * CARA MENJALANKAN
 * ----------------
 * 1. Windows Task Scheduler (disarankan):
 *      Program : C:\xampp\php\php.exe
 *      Arguments: C:\xampp\htdocs\Perpustakaan\Cron\reminder.php
 *      Trigger : setiap 1 jam, atau setiap 15 menit
 *
 * 2. Dari command line:
 *      C:\xampp\php\php.exe C:\xampp\htdocs\Perpustakaan\Cron\reminder.php
 *
 * Script ini idempoten. Menjalankannya 100x dalam satu jam tetap aman:
 * notifikasi yang sama tidak akan dibuat dua kali (dedup_key UNIQUE).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Script ini hanya boleh dijalankan dari command line.');
}

require_once __DIR__ . '/../Config/koneksi.php';
require_once __DIR__ . '/../Config/bootstrap.php';

use App\Services\DendaService;
use App\Services\ReminderService;

$reminder = new ReminderService($conn, new DendaService($conn));

echo '[' . date('Y-m-d H:i:s') . '] Menjalankan pengingat tenggat...' . PHP_EOL;

try {
    $hasil = $reminder->jalankan();

    printf(
        "  Peminjaman diperiksa : %d\n  Notifikasi baru     : %d\n",
        $hasil['diperiksa'],
        $hasil['notifikasi_baru']
    );

    $ringkasan = $reminder->ringkasanTunggakan();
    printf(
        "  Peminjaman aktif    : %d\n  Terlambat >1 hari   : %d\n  Terlambat >3 hari   : %d\n  Terlambat >7 hari   : %d\n  Proyeksi total denda: Rp%s\n",
        $ringkasan['aktif'],
        $ringkasan['lewat_1'],
        $ringkasan['lewat_3'],
        $ringkasan['lewat_7'],
        number_format($ringkasan['total_denda'], 0, ',', '.')
    );

    echo 'Selesai.' . PHP_EOL;
    exit(0);
} catch (Throwable $ex) {
    fwrite(STDERR, 'GAGAL: ' . $ex->getMessage() . PHP_EOL);

    try {
        $conn->prepare(
            "INSERT INTO job_log (nama_job, status, diproses, pesan)
             VALUES ('reminder_tenggat', 'Gagal', 0, :pesan)"
        )->execute([':pesan' => mb_substr($ex->getMessage(), 0, 250)]);
    } catch (Throwable $abaikan) {
        // Jangan sampai gagalnya pencatatan log menutupi error asli.
    }

    exit(1);
}
