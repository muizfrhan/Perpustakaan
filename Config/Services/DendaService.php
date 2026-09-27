<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * Sumber tunggal (single source of truth) untuk perhitungan denda.
 *
 * SEBELUMNYA logika ini tersebar sebagai magic number di dalam controller:
 *   Admin/Pengembalian/add_pengembalian.php:40  -> 5000 * $hari_terlambat
 *   Admin/Pengembalian/add_pengembalian.php:45  -> +20000
 *   Admin/Pengembalian/add_pengembalian.php:48  -> +50000
 *
 * Masalahnya:
 *  1. Angkaaurantsia tidak bisa diubah admin tanpa mengedit kode & deploy ulang.
 *  2. $hari_terlambat dihitung dengan selisih timestamp 86400 detik, sehingga
 *     menghasilkan pecahan (mis. 1.583 hari) -> denda Rp7.916,66.
 *  3. Tidak ada grace period maupun batas atas, sehingga keterlambatan
 *    yang sangat lama bisa menghasilkan tagihan yang tidak proporsional.
 *
 * KEPUTUSAN DESAIN:
 *  - Denda dihitung per HARI KALENDER, bukan blok 24 jam. Perpustakaan
 *   Perpustakaan tutup pada hari libur tetap dihitung 1 hari; ini standarnya
 *    hampir semua sistem perpustakaan dunia.
 *  - Grace period ditolak dari total keterlambatan (bukan ditambahkan ke denda).
 *  - hari_maks sebagai CAP: melindungi mahasiswa dari denda tak masuk akal.
 *  - Seluruh nominal sebagai integer rupiah.
 */
final class DendaService
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $cacheAturan = null;

    /**
     * @param PDO                  $db
     * @param array<string, mixed> $aturanOverride Hanya untuk unit test. null = baca dari DB.
     */
    public function __construct(
        private readonly PDO $db,
        private readonly ?array $aturanOverride = null,
    ) {
    }

    // -----------------------------------------------------------------------
    // API utama
    // -----------------------------------------------------------------------

    /**
     * Hitung denda pengembalian.
     *
     * @param string      $tglEstimasi  Batas pengembalian (bisa 'Y-m-d' atau 'Y-m-d H:i:s')
     * @param string|null $tglKembali  Tanggal dikembalikan. null = belum kembali (proyeksi hari ini)
     */
    public function hitung(
        string $tglEstimasi,
        ?string $tglKembali = null,
        KondisiBuku $kondisi = KondisiBuku::Bagus,
    ): DendaResult {
        $batas     = $this->keTanggal($tglEstimasi);
        $kembali   = $tglKembali === null ? new DateTimeImmutable('today') : $this->keTanggal($tglKembali);

        // Selisih hari kalender. Diff dalam hari absolut, tanpa komponen jam.
        $selisihHari = (int) $batas->diff($kembali)->format('%r%a');
        $terlambat   = $selisihHari > 0;

        $terlambatHari = max(0, $selisihHari);

        // --- Denda keterlambatan ------------------------------------------------
        $aturanTerlambat = $this->aturan('TERLAMBAT');
        $graceHari        = (int) ($aturanTerlambat['grace_hari'] ?? 0);
        $hariMaks         = $aturanTerlambat['hari_maks'] !== null
            ? (int) $aturanTerlambat['hari_maks']
            : null;
        $tarifPerHari     = (int) $aturanTerlambat['nominal'];

        // Grace period = potongan hari, bukan tambahan denda.
        $setelahGrace = max(0, $terlambatHari - $graceHari);

        // Cap atas supaya tidak menghasilkan tagihan yang tidak proporsional.
        $hariDikenakan = $hariMaks !== null ? min($setelahGrace, $hariMaks) : $setelahGrace;

        $dendaTerlambat = $hariDikenakan * $tarifPerHari;

        // --- Denda kondisi -----------------------------------------------------
        [$dendaKondisi, $namaKondisi] = $this->dendaKondisi($kondisi);

        // --- Rincian audit -----------------------------------------------------
        $bagian = [];

        if ($hariDikenakan > 0) {
            $catatan = sprintf(
                'Terlambat %d hari, dikurangi grace %d hari',
                $terlambatHari,
                $graceHari
            );

            if ($hariMaks !== null && $setelahGrace > $hariMaks) {
                $catatan .= sprintf(', dibatasi maksimum %d hari', $hariMaks);
            }

            $bagian[] = sprintf(
                '%s = %d hari x %s = %s',
                $catatan,
                $hariDikenakan,
                number_format($tarifPerHari, 0, ',', '.'),
                number_format($dendaTerlambat, 0, ',', '.')
            );
        } else {
            $bagian[] = $terlambat
                ? sprintf('Terlambat %d hari, seluruhnya jatuh pada grace period', $terlambatHari)
                : 'Tepat waktu, tidak ada denda keterlambatan';
        }

        if ($dendaKondisi > 0) {
            $bagian[] = sprintf(
                'Denda %s = %s',
                $namaKondisi,
                number_format($dendaKondisi, 0, ',', '.')
            );
        }

        return new DendaResult(
            hariTerlambat:  $terlambatHari,
            graceHari:       $graceHari,
            hariDikenakan:   $hariDikenakan,
            tarifPerHari:    $tarifPerHari,
            dendaTerlambat:  $dendaTerlambat,
            dendaKondisi:    $dendaKondisi,
            dasarHitung:     implode('. ', $bagian) . '.',
            terlambat:       $terlambat,
        );
    }

    /**
     * Proyeksi denda saat ini untuk peminjaman yang belum dikembalikan.
     * Dipakai untuk notifikasi dan badge "terlambat" di katalog.
     */
    public function proyeksi(string $tglEstimasi, KondisiBuku $kondisi = KondisiBuku::Bagus): DendaResult
    {
        return $this->hitung($tglEstimasi, null, $kondisi);
    }

    /**
     * Sisa hari menuju tenggat. Negatif = sudah terlambat.
     */
    public function sisaHari(string $tglEstimasi, ?string $acuan = null): int
    {
        $batas = $this->keTanggal($tglEstimasi);
        $today = $acuan === null ? new DateTimeImmutable('today') : $this->keTanggal($acuan);

        return -((int) $batas->diff($today)->format('%r%a'));
    }

    // -----------------------------------------------------------------------
    // internally helpers
    // -----------------------------------------------------------------------

    /**
     * Normalisasi ke tanggal TANPA jam. Inilah perbaikan inti bug "/86400 = 1.583 hari":
     * jam 00:00 pada tanggal kembali tidak lagi menggeser hasil satu hari.
     */
    private function keTanggal(string $nilai): DateTimeImmutable
    {
        $nilai = trim($nilai);

        if ($nilai === '') {
            throw new RuntimeException('Tanggal tidak boleh kosong.');
        }

        // Toleransi format 'YYYY-MM-DD HH:MM:SS' maupun 'YYYY-MM-DD'.
        $coba = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $nilai)
            ?: DateTimeImmutable::createFromFormat('Y-m-d', $nilai)
            ?: DateTimeImmutable::createFromFormat('d/m/Y', $nilai);

        if ($coba === false) {
            throw new RuntimeException("Format tanggal tidak dikenali: {$nilai}");
        }

        return $coba->setTime(0, 0);
    }

    /** @return array{0:int, 1:string} [nominal, nama aturan] */
    private function dendaKondisi(KondisiBuku $kondisi): array
    {
        return match ($kondisi) {
            KondisiBuku::Rusak  => [(int) $this->aturan('RUSAK')['nominal'],  'Buku rusak'],
            KondisiBuku::Hilang => [(int) $this->aturan('HILANG')['nominal'], 'Buku hilang'],
            KondisiBuku::Bagus  => [0, 'Buku baik'],
        };
    }

    /**
     * Ambil satu aturan dari cache. Tabel `aturan_denda` dibaca satu kali
     * per request supaya tidak query berulang-ulang.
     *
     * @return array<string, mixed>
     */
    private function aturan(string $kode): array
    {
        $this->cacheAturan ??= $this->muatAturan();

        return $this->cacheAturan[$kode] ?? [
            'nominal'    => 0,
            'grace_hari' => 0,
            'hari_maks'  => null,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function muatAturan(): array
    {
        if ($this->aturanOverride !== null) {
            return $this->aturanOverride;
        }

        $hasil = [];

        foreach ($this->db->query('SELECT kode_aturan, nominal, grace_hari, hari_maks FROM aturan_denda WHERE aktif = 1')->fetchAll() as $baris) {
            // DECIMAL datang sebagai string dari PDO; cast eksplisit.
            $hasil[$baris['kode_aturan']] = [
                'nominal'    => (int) round((float) $baris['nominal']),
                'grace_hari' => (int) $baris['grace_hari'],
                'hari_maks'  => $baris['hari_maks'] === null ? null : (int) $baris['hari_maks'],
            ];
        }

        return $hasil;
    }
}
