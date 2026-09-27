<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Penghasil nomor transaksi yang aman terhadap race condition.
 *
 * MASALAH YANG DISOLUSIKAN
 * ------------------------
 * Pola lama di add_pengembalian.php dan add_peminjaman.php:
 *
 *     SELECT kode_pinjam FROM peminjaman ORDER BY kode_pinjam DESC LIMIT 1
 *     $kode = "PN" . ((int) substr($last, 2) + 1);
 *
 * Dua petugas yang memproses transaksi pada detik yang sama akan membaca
 * nilai MAX yang sama dan menghasilkan kode yang sama. Di database lokal
 * jarang terjadi, tapi di server produksi dengan banyak petugas aktif
 * duplikat kode berarti transaksi hilang.
 *
 * SOLUSI
 * ------
 * Tabel `sequence` (lihat migration_002). Pola di bawah memakai
 * LAST_INSERT_ID(expr) yang dievaluasi di dalam statement yang sama, sehingga
 * operasi "ambil nomor berikutnya" bersifat atomik, bahkan tanpa lock eksplisit.
 * Ini pola standar MySQL/MariaDB untuk sequence.
 */
final class KodeTransaksi
{
    private const POLA = [
        'kode_pinjam'  => ['prefix' => 'PN', 'lebar' => 4],
        'kode_kembali' => ['prefix' => 'KB', 'lebar' => 4],
    ];

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Ambil nomor transaksi berikutnya, mis. "PN0007" / "KB0007".
     *
     * Panjang hasil harus muat di kolom terkait. Peminjaman.kode_pinjam
     * dan pengembalian.kode_kembali masing-masing varchar(6)/(10).
     */
    public function berikutnya(string $nama): string
    {
        $konfigurasi = self::POLA[$nama] ?? throw new \InvalidArgumentException(
            "Sequence \"{$nama}\" tidak terdaftar."
        );

        // LAST_INSERT_ID(1) dipakai saat baris baru dibuat,
        // LAST_INSERT_ID(next_val + 1) saat baris sudah ada.
        $stmt = $this->db->query(
            "INSERT INTO `sequence` (`nama`, `next_val`)
             VALUES ('{$nama}', LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE `next_val` = LAST_INSERT_ID(`next_val` + 1)"
        );

        $stmt->fetchAll();

        $nomor = (int) $this->db->query('SELECT LAST_INSERT_ID()')->fetchColumn();

        if ($nomor < 1) {
            throw new \RuntimeException("Gagal mengambil nomor sequence untuk {$nama}.");
        }

        return $konfigurasi['prefix'] . str_pad(
            (string) $nomor,
            $konfigurasi['lebar'],
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * Selaraskan sequence dengan data yang sudah ada.
     *
     * WAJIB dipanggil setelah import dump SQL lama, karena tabel `sequence`
     * baru mulai dari 1 sementara `peminjaman` sudah berisi PN001..PN250.
     * Tanpa ini, kode berikutnya akan bentrok dengan PRIMARY KEY.
     */
    public function selaraskan(): void
    {
        $this->selaraskanSatu('kode_pinjam', 'peminjaman', 'kode_pinjam', 'PN');
        $this->selaraskanSatu('kode_kembali', 'pengembalian', 'kode_kembali', 'KB');
    }

    private function selaraskanSatu(string $sequence, string $tabel, string $kolom, string $prefix): void
    {
        $tertinggi = $this->db->query(
            "SELECT {$kolom} FROM `{$tabel}` WHERE {$kolom} LIKE '{$prefix}%' ORDER BY {$kolom} DESC LIMIT 1"
        )->fetchColumn();

        $nilai = $tertinggi === false ? 1 : ((int) substr($tertinggi, strlen($prefix)) + 1);

        $stmt = $this->db->prepare(
            'INSERT INTO `sequence` (`nama`, `next_val`) VALUES (:nama, :nilai)
             ON DUPLICATE KEY UPDATE `next_val` = GREATEST(`next_val`, :nilai2)'
        );

        $stmt->execute([':nama' => $sequence, ':nilai' => $nilai, ':nilai2' => $nilai]);
    }
}
