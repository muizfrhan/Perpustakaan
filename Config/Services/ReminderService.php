<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;

/**
 * Deteksi tenggat pengembalian & pembuatan notifikasi.
 *
 * PRINSIP IDEMPOTENSI
 * ------------------
 * Job ini berjalan berkala (cron / Windows Task Scheduler). Kalau tidak
 * idempoten, anggota bisa menerima ratusan notifikasi duplikat per hari.
 *
 * Setiap notifikasi memiliki `dedup_key` dengan UNIQUE index
 * (lihat Database/migration_002_denda_notifikasi.sql). Insert memakai
 * INSERT IGNORE sehingga percobaan kedua diabaikan oleh database, bukan
 * oleh logika PHP. Ini aman terhadap race condition antar-proses.
 *
 * dedup_key memuat TAHAP pengingat, mis. "tenggat:PN001:H-3", sehingga
 * pengingat H-3, H-1, dan H+3 tetap dihitung sebagai tiga pesan berbeda.
 */
final class ReminderService
{
    /** Pengingat sebelum tenggat (sisa hari). */
    private const SEBELUM_TENGGAT = [3, 1];

    /** Pengingat setelah tenggat (hari keterlambatan). */
    private const SETELAH_TENGGAT = [0, 3, 7];

    /** @var list<array<string, mixed>>|null */
    private ?array $cacheAktif = null;

    public function __construct(
        private readonly PDO $db,
        private readonly DendaService $denda,
    ) {
    }

    /**
     * Jalankan satu siklus pengingat. Aman dipanggil berulang kali.
     *
     * @return array{diperiksa:int, notifikasi_baru:int}
     */
    public function jalankan(?DateTimeImmutable $sekarang = null): array
    {
        $sekarang ??= new DateTimeImmutable('today');
        $acuan     = $sekarang->format('Y-m-d');
        $terbaru   = 0;

        foreach ($this->peminjamanAktif() as $p) {
            // Negatif = belum jatuh tempo. Positif = sudah terlambat.
            $selisihHari = $this->denda->sisaHari($p['estimasi_pinjam'], $acuan);

            if ($selisihHari >= 0) {
                // --- Sebelum tenggat ---------------------------------------------
                if (in_array($selisihHari, self::SEBELUM_TENGGAT, true)) {
                    $terbaru += $this->kirim(
                        penerimaType: 'Anggota',
                        penerimaId:   (int) $p['nim'],
                        judul:       'Pengingat: buku hampir jatuh tempo',
                        pesan:       sprintf(
                            'Buku "%s" harus dikembalikan dalam %d hari lagi (%s). '
                            . 'Keterlambatan dikenakan denda Rp%s per hari.',
                            $p['judul_buku'],
                            $selisihHari,
                            $this->formatTanggal($p['estimasi_pinjam']),
                            number_format($this->tarifHarian(), 0, ',', '.')
                        ),
                        tipe:        'Peringatan',
                        kategori:    'Tenggat',
                        refTable:    'peminjaman',
                        refId:       $p['kode_pinjam'],
                        dedupKey:    sprintf('tenggat:%s:H-%d', $p['kode_pinjam'], $selisihHari),
                    );
                }

                continue;
            }

            // --- Setelah tenggat ----------------------------------------------
            $terlambat = -$selisihHari;

            if (!in_array($terlambat, self::SETELAH_TENGGAT, true)) {
                continue;
            }

            $proyeksi = $this->denda->proyeksi($p['estimasi_pinjam'], KondisiBuku::Bagus);

            $terbaru += $this->kirim(
                penerimaType: 'Anggota',
                penerimaId:   (int) $p['nim'],
                judul:       'Buku terlambat: segera dikembalikan',
                pesan:       sprintf(
                    'Buku "%s" sudah terlambat %d hari. Denda keterlambatan saat ini %s '
                    . 'dan bertambah Rp%s per hari. Mohon segera dikembalikan ke perpustakaan.',
                    $p['judul_buku'],
                    $terlambat,
                    $proyeksi->formatRupiah(),
                    number_format($this->tarifHarian(), 0, ',', '.')
                ),
                tipe:        $terlambat >= 3 ? 'Kritis' : 'Peringatan',
                kategori:    'Terlambat',
                refTable:    'peminjaman',
                refId:       $p['kode_pinjam'],
                dedupKey:    sprintf('terlambat:%s:H+%d', $p['kode_pinjam'], $terlambat),
            );

            // Petugas ikut diberi tahu agar bisa menindaklanjuti.
            if ($terlambat >= 3 && !empty($p['id_petugas'])) {
                $terbaru += $this->kirim(
                    penerimaType: 'Petugas',
                    penerimaId:   (int) $p['id_petugas'],
                    judul:       'Ada anggota yang menunggak pengembalian',
                    pesan:       sprintf(
                        'Peminjaman %s oleh anggota NIM %s terlambat %d hari. '
                        . 'Denda berjalan %s. Segera hubungi anggota.',
                        $p['kode_pinjam'],
                        $p['nim'],
                        $terlambat,
                        $proyeksi->formatRupiah()
                    ),
                    tipe:        $terlambat >= 7 ? 'Kritis' : 'Peringatan',
                    kategori:    'Terlambat',
                    refTable:    'peminjaman',
                    refId:       $p['kode_pinjam'],
                    dedupKey:    sprintf('petugas:%s:H+%d', $p['kode_pinjam'], $terlambat),
                );
            }
        }

        $this->catatJob($terbaru);

        return [
            'diperiksa'      => count($this->peminjamanAktif()),
            'notifikasi_baru' => $terbaru,
        ];
    }

    /**
     * Semua peminjaman yang belum dikembalikan.
     *
     * Memakai LEFT JOIN ke pengembalian (bukan NOT IN) supaya null-safe,
     * dan TIDAK bisa salah kalau tabel pengembalian kosong.
     *
     * @return list<array<string, mixed>>
     */
    public function peminjamanAktif(): array
    {
        return $this->cacheAktif ??= $this->db->query(
            "SELECT p.kode_pinjam, p.nim, p.id_petugas, p.estimasi_pinjam, p.tgl_pinjam, b.judul_buku
             FROM peminjaman p
             JOIN buku b ON b.kode_buku = p.kode_buku
             LEFT JOIN pengembalian g ON g.kode_pinjam = p.kode_pinjam
             WHERE p.status = 'Dipinjam'
               AND g.kode_pinjam IS NULL"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ringkasan tunggakan untuk badge di dashboard.
     *
     * @return array{aktif:int, lewat_1:int, lewat_3:int, lewat_7:int, total_denda:int}
     */
    public function ringkasanTunggakan(?DateTimeImmutable $sekarang = null): array
    {
        $acuan = ($sekarang ?? new DateTimeImmutable('today'))->format('Y-m-d');

        $hasil = [
            'aktif'        => 0,
            'lewat_1'      => 0,
            'lewat_3'      => 0,
            'lewat_7'      => 0,
            'total_denda'  => 0,
        ];

        foreach ($this->peminjamanAktif() as $p) {
            $hasil['aktif']++;

            $terlambat = -$this->denda->sisaHari($p['estimasi_pinjam'], $acuan);

            if ($terlambat <= 0) {
                continue;
            }

            $hasil['total_denda'] += $this->denda
                ->proyeksi($p['estimasi_pinjam'], KondisiBuku::Bagus)
                ->total();

            foreach ([1, 3, 7] as $ambang) {
                if ($terlambat >= $ambang) {
                    $hasil['lewat_' . $ambang]++;
                }
            }
        }

        return $hasil;
    }

    /**
     * Notifikasi belum dibaca milik seorang pengguna.
     *
     * @return list<array<string, mixed>>
     */
    public function unread(string $tipe, int $id, int $limit = 20): array
    {
        // $limit sudah dipastikan integer, bukan string dari user.
        $stmt = $this->db->prepare(
            'SELECT id, judul, pesan, tipe, kategori, ref_table, ref_id, created_at
             FROM notifikasi
             WHERE penerima_type = :tipe AND penerima_id = :id AND dibaca = 0
             ORDER BY created_at DESC, id DESC
             LIMIT ' . max(1, min(100, $limit))
        );

        $stmt->execute([':tipe' => $tipe, ':id' => $id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tandaiDibaca(int $notifId, string $tipe, int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE notifikasi
             SET dibaca = 1, dibaca_at = NOW()
             WHERE id = :id AND penerima_type = :tipe AND penerima_id = :penerima'
        );

        $stmt->execute([':id' => $notifId, ':tipe' => $tipe, ':penerima' => $id]);

        return $stmt->rowCount() > 0;
    }

    // -----------------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------------

    /**
     * INSERT IGNORE + dedup_key UNIQUE = idempotent di level database.
     * Mengembalikan 1 kalau notifikasi baru dibuat, 0 kalau sudah ada.
     */
    private function kirim(
        string  $penerimaType,
        int     $penerimaId,
        string  $judul,
        string  $pesan,
        string  $tipe,
        string  $kategori,
        ?string $refTable,
        ?string $refId,
        string  $dedupKey,
    ): int {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO notifikasi
                (penerima_type, penerima_id, judul, pesan, tipe, kategori, ref_table, ref_id, kanal, dedup_key)
             VALUES (:tipe, :id, :judul, :pesan, :jenis, :kategori, :ref_table, :ref_id, :kanal, :dedup)'
        );

        $stmt->execute([
            ':tipe'      => $penerimaType,
            ':id'        => $penerimaId,
            ':judul'     => $judul,
            ':pesan'     => $pesan,
            ':jenis'     => $tipe,
            ':kategori'  => $kategori,
            ':ref_table' => $refTable,
            ':ref_id'    => $refId,
            ':kanal'     => 'InApp',
            ':dedup'     => $dedupKey,
        ]);

        return $stmt->rowCount();
    }

    private function tarifHarian(): int
    {
        $nilai = $this->db
            ->query("SELECT nominal FROM aturan_denda WHERE kode_aturan = 'TERLAMBAT' AND aktif = 1")
            ->fetchColumn();

        return $nilai === false ? 0 : (int) round((float) $nilai);
    }

    private function formatTanggal(string $nilai): string
    {
        $waktu = strtotime($nilai);

        return $waktu === false ? $nilai : date('d/m/Y', $waktu);
    }

    private function catatJob(int $diproses): void
    {
        $this->db->prepare(
            "INSERT INTO job_log (nama_job, status, diproses, pesan)
             VALUES ('reminder_tenggat', 'Sukses', :n, 'Pengingat tenggat dijalankan')"
        )->execute([':n' => $diproses]);
    }
}
