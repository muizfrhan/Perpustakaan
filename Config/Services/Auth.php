<?php

namespace App\Services;

use PDO;

/**
 * Autentikasi terpadu.
 *
 * Aplikasi ini punya tiga tabel akun (owner, petugas, anggota) yang
 * dulunya punya tiga halaman login terpisah. Auth::attempt() menggabungkan
 * ketiganya jadi satu pemeriksaan, sehingga pengguna cukup mengetik
 * username/NIM + password tanpa memilih peran.
 *
 * CATATAN KEAMANAN
 * Password di ketiga tabel masih disimpan sebagai plaintext (sudah begitu
 * sejak awal, tidak diubah di sini). Method ini mempertahankan perilaku
 * tersebut agar akun yang sudah ada tidak ikut rusak. Mengganti ke
 * password_hash() adalah langkah terpisah yang perlu migrasi data.
 */
final class Auth
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER  = 'user';

    /**
     * Halaman tujuan setelah login berhasil, relatif terhadap root project.
     */
    public const REDIRECT = [
        self::ROLE_OWNER => 'Owner/Dashboard/dashboard.php',
        self::ROLE_ADMIN => 'Admin/Dashboard/dashboard.php',
        self::ROLE_USER  => 'User/home.php',
    ];

    /** Label peran untuk ditampilkan di UI. */
    public const LABEL = [
        self::ROLE_OWNER => 'Owner',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_USER  => 'Anggota',
    ];

    /**
     * Coba login dengan satu identifier + password.
     *
     * Identifier boleh berupa username (owner/petugas) ATAU NIM (anggota);
     * sistem akan mencoba ketiganya secara otomatis. Password dibandingkan
     * dengan password_verify() bila tersimpan sebagai hash, dan apa adanya
     * bila masih plaintext.
     *
     * @return array{role:string,id:mixed,nama:string}|null
     *         null bila tidak ada yang cocok.
     */
    public static function attempt(PDO $conn, string $identifier, string $password): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            return null;
        }

        // Urutan menentukan peran bila username/NIM kebetulan sama.
        foreach (['cobaOwner', 'cobaPetugas', 'cobaAnggota'] as $metode) {
            $hasil = self::$metode($conn, $identifier, $password);
            if ($hasil !== null) {
                return $hasil;
            }
        }

        return null;
    }

    private static function cobaOwner(PDO $conn, string $identifier, string $password): ?array
    {
        $stmt = $conn->prepare(
            'SELECT id_owner, username, password, nama_pemilik, profil_gambar FROM owner WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !self::cocok($password, (string) $row['password'])) {
            return null;
        }

        return [
            'role' => self::ROLE_OWNER,
            'id'   => $row['id_owner'],
            'nama' => $row['nama_pemilik'],
            'foto' => $row['profil_gambar'] ?? null,
        ];
    }

    private static function cobaPetugas(PDO $conn, string $identifier, string $password): ?array
    {
        $stmt = $conn->prepare(
            'SELECT id_petugas, username, password, nama_petugas, profil_gambar FROM petugas WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !self::cocok($password, (string) $row['password'])) {
            return null;
        }

        return [
            'role' => self::ROLE_ADMIN,
            'id'   => $row['id_petugas'],
            'nama' => $row['nama_petugas'],
            'foto' => $row['profil_gambar'] ?? null,
        ];
    }

    private static function cobaAnggota(PDO $conn, string $identifier, string $password): ?array
    {
        // NIM adalah angka. Jangan casting ke int supaya tidak meledak bila
        // pengguna mengetik karakter aneh ke kolom yang sebenarnya varchar.
        if (!preg_match('/^\d+$/', $identifier)) {
            return null;
        }

        $stmt = $conn->prepare(
            'SELECT nim, password, nama, profil_gambar FROM anggota WHERE nim = :nim LIMIT 1'
        );
        $stmt->execute([':nim' => $identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !self::cocok($password, (string) $row['password'])) {
            return null;
        }

        return [
            'role' => self::ROLE_USER,
            'id'   => $row['nim'],
            'nama' => $row['nama'],
            'foto' => $row['profil_gambar'] ?? null,
        ];
    }

    /**
     * Bandingkan password dengan yang tersimpan.
     *
     * Mendukung dua format sekaligus: hash (password_verify) dan plaintext,
     * sehingga migrasi ke hashing bisa dilakukan bertahap.
     */
    private static function cocok(string $input, string $tersimpan): bool
    {
        if ($tersimpan === '') {
            return false;
        }

        if (str_starts_with($tersimpan, '$2y$') || str_starts_with($tersimpan, '$argon2')) {
            return password_verify($input, $tersimpan);
        }

        return hash_equals($tersimpan, $input);
    }

    /**
     * Baca ulang satu akun berdasarkan peran + id, TANPA memeriksa password.
     *
     * Dipakai oleh "Ingat Saya" (lihat Config/Services/IngatLogin.php).
     * Token remember-me yang sudah tersimpan harus tetap diverifikasi terhadap
     * data akun yang paling sekarang: akun bisa saja sudah dihapus,
     * dinonaktifkan, atau ganti peran sejak token itu diterbitkan.
     *
     * Bentuk kembalian sama dengan attempt() supaya Auth::loginSession()
     * bisa langsung dipakai.
     *
     * @return array{role:string,id:mixed,nama:string,foto:?string}|null
     */
    public static function loadByRole(PDO $conn, string $role, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        // Petugas dan anggota punya kolom status; akun nonaktif tidak boleh
        // diteruskan hanya karena token-nya masih valid.
        switch ($role) {
            case self::ROLE_OWNER:
                $sql = 'SELECT id_owner, nama_pemilik, profil_gambar
                          FROM owner WHERE id_owner = :id LIMIT 1';
                break;

            case self::ROLE_ADMIN:
                $sql = "SELECT id_petugas, nama_petugas, profil_gambar
                          FROM petugas
                         WHERE id_petugas = :id AND status = 'Aktif'
                         LIMIT 1";
                break;

            default:
                $sql = "SELECT nim, nama, profil_gambar
                          FROM anggota
                         WHERE nim = :id AND status_mhs = 'Aktif'
                         LIMIT 1";
                break;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return match ($role) {
            self::ROLE_OWNER => [
                'role' => self::ROLE_OWNER,
                'id'   => $row['id_owner'],
                'nama' => $row['nama_pemilik'],
                'foto' => $row['profil_gambar'] ?: null,
            ],
            self::ROLE_ADMIN => [
                'role' => self::ROLE_ADMIN,
                'id'   => $row['id_petugas'],
                'nama' => $row['nama_petugas'],
                'foto' => $row['profil_gambar'] ?: null,
            ],
            default => [
                'role' => self::ROLE_USER,
                'id'   => $row['nim'],
                'nama' => $row['nama'],
                'foto' => $row['profil_gambar'] ?: null,
            ],
        };
    }

    /**
     * Tulis session sesuai peran.
     *
     * Nama key di sini HARUS sama dengan yang dibaca auth guard di
     * Config/bootstrap.php (id_owner / id_petugas / nim) dan header sidebar
     * (nama_pemilik / nama_petugas / nama).
     */
    public static function loginSession(array $hasil): void
    {
        // Bersihkan sisa session peran lain sebelum menyetel yang baru.
        unset($_SESSION['id_owner'], $_SESSION['id_petugas'], $_SESSION['nim']);

        switch ($hasil['role']) {
            case self::ROLE_OWNER:
                $_SESSION['id_owner']     = $hasil['id'];
                $_SESSION['nama_pemilik'] = $hasil['nama'];
                break;

            case self::ROLE_ADMIN:
                $_SESSION['id_petugas']    = $hasil['id'];
                $_SESSION['nama_petugas']  = $hasil['nama'];
                break;

            default:
                $_SESSION['nim']  = $hasil['id'];
                $_SESSION['nama'] = $hasil['nama'];
                break;
        }

        // Catat peran supaya UI bisa menyesuaikan tanpa menebak dari key.
        $_SESSION['peran'] = $hasil['role'];

        // Foto profil, supaya sidebar/navbar tidak perlu query ulang.
        $_SESSION['profil_gambar'] = $hasil['foto'] ?? null;
    }

    /**
     * Hapus seluruh session lalu kembalikan URL login.
     */
    public static function logout(): string
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();

        return 'login.php';
    }
}
