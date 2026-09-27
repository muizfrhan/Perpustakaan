<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Hasil perhitungan denda. Immutable, aman dioper ke mana saja.
 *
 * Semua nominal memakai INTEGER RUPIAH, bukan float. Penyebab: uang tidak
 * boleh direpresentasikan sebagai floating point (0.1 + 0.2 !== 0.3), dan
 * kolom `denda` di tabel pengembalian bertipe DECIMAL(12,2).
 */
final readonly class DendaResult
{
    public function __construct(
        /** Jumlah hari kalender terlambat, sebelum grace period & cap. */
        public int $hariTerlambat,

        /** Hari tenggang yang tidak dimewakan (mis. 1 hari). */
        public int $graceHari,

        /** Jumlah hari yang benar-benar ditagihkan (setelah grace & cap). */
        public int $hariDikenakan,

        /** Tarif harian yang berlaku saat perhitungan, dari tabel aturan_denda. */
        public int $tarifPerHari,

        /** Denda keterlambatan = hariDikenakan x tarifPerHari. */
        public int $dendaTerlambat,

        /** Denda tetap sesuai kondisi buku. */
        public int $dendaKondisi,

        /** Ringkasan teks untuk disimpan di kolom `dasar_hitung` (audit). */
        public string $dasarHitung,

        /** True bila tenggat sudah terlampaui. */
        public bool $terlambat,
    ) {
    }

    public function total(): int
    {
        return $this->dendaTerlambat + $this->dendaKondisi;
    }

    public function formatRupiah(): string
    {
        return 'Rp' . number_format($this->total(), 0, ',', '.');
    }

    /** Ringkasan satu baris untuk ditampilkan di UI. */
    public function ringkas(): string
    {
        return sprintf(
            'Terlambat %d hari (dikenakan %d hari x %s) + kondisi (%s) = %s',
            $this->hariTerlambat,
            $this->hariDikenakan,
            number_format($this->tarifPerHari, 0, ',', '.'),
            number_format($this->dendaKondisi, 0, ',', '.'),
            $this->formatRupiah()
        );
    }
}
