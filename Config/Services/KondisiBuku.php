<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Kondisi buku saat dikembalikan.
 *
 * Nilai enum SENGAJA sama persis dengan definisi ENUM di tabel `pengembalian`
 * ('Bagus','Rusak','Hilang') karena MariaDB/MySQL membandingkan enum
 * case-sensitively saat menyimpan.
 *
 * Formulir HTML lama mengirim nilai lowercase ("bagus"). fromInput()
 * menormalkan agar tidak ada ketergantungan pada SQL mode.
 */
enum KondisiBuku: string
{
    case Bagus  = 'Bagus';
    case Rusak  = 'Rusak';
    case Hilang = 'Hilang';

    /**
     * Terima input apa pun dari form/API dan normalkan ke enum.
     * Nilai tidak dikenal dianggap 'Bagus' agar tidak memblokir transaksi.
     */
    public static function fromInput(mixed $nilai): self
    {
        if ($nilai instanceof self) {
            return $nilai;
        }

        return match (mb_strtolower(trim((string) $nilai))) {
            'rusak'  => self::Rusak,
            'hilang' => self::Hilang,
            default  => self::Bagus,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Bagus  => 'Kondisi baik',
            self::Rusak  => 'Rusak',
            self::Hilang => 'Hilang',
        };
    }
}
