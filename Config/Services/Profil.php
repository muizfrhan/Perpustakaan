<?php

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Pengaturan akun untuk ketiga peran (owner, petugas, anggota).
 *
 * Semua aturan peran TIDAK di-hardcode di halaman, melainkanditempatkan di sini
 * supaya ketiga halaman pengaturan (Admin/Akun, Owner/Akun, User/account)
 * memakai sumber yang sama dan tidak bisa berbeda-eja.
 *
 * Prinsip keamanan yang ditegakkan kelas ini:
 *  - Kolom yang boleh diedit ditetapkan per peran. `status` dan
 *    `status_mhs` SENGAJA TIDAK bisa diubah lewat halaman pengaturan:
 *    kalau bisa, anggota bisa mengaktifkan dirinya sendiri dan petugas
 *    bisa mengaktifkan akunnya sendiri - eskalasi privilege.
 *  - Nama kolom selalu berasal dari allowlist, tidak pernah dari input
 *    pengguna. Ini menutup SQL injection lewat nama kolom.
 *  - Upload foto divalidasi berdasarkan isi file (getimagesize), bukan
 *    hanya ekstensi/Content-Type dari browser.
 *  - Password dibandingkan dengan hash_equals() dan mendukung hash
 *    maupun plaintext warisan.
 */
final class Profil
{
    /** Batas ukuran foto: 2 MB. Cukup untuk foto profil, blocking sheets. */
    public const MAKS_UKURAN = 2 * 1024 * 1024;

    /** Panjang minimum password baru. */
    public const MIN_SANDI = 8;

    /** Format gambar yang boleh diunggah -> ekstensi hasil. */
    private const TIPE_GAMBAR = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Definisi per peran.
     *
     * tabel     : tabel sumber data
     * pk        : primary key
     * id        : nama session yang menandai login
     * kolom     : kolom yang boleh diedit lewat halaman pengaturan
     * wajib     : kolom yang wajib terisi
     * label     : nama kolom sesi yang menyimpan nama tampilan
     * adaUsername : apakah peran punya kolom username
     * adaSandi   : apakah peran punya kolom password
     */
    public const PERAN = [
        'owner' => [
            'label' => 'Owner',
            'tabel' => 'owner',
            'pk'    => 'id_owner',
            'id'    => 'id_owner',
            'kolom' => ['nama_pemilik', 'username', 'profil_gambar'],
            'wajib' => ['nama_pemilik', 'username'],
            'sesiNama' => 'nama_pemilik',
            'adaUsername' => true,
            'adaSandi'   => true,
        ],
        'petugas' => [
            'label' => 'Admin',
            'tabel' => 'petugas',
            'pk'    => 'id_petugas',
            'id'    => 'id_petugas',
            'kolom' => ['nama_petugas', 'username', 'no_telp', 'jenis_kelamin', 'profil_gambar'],
            'wajib' => ['nama_petugas', 'username', 'no_telp', 'jenis_kelamin'],
            'sesiNama' => 'nama_petugas',
            'adaUsername' => true,
            'adaSandi'   => true,
        ],
        'anggota' => [
            'label' => 'Anggota',
            'tabel' => 'anggota',
            'pk'    => 'nim',
            'id'    => 'nim',
            'kolom' => ['nama', 'no_telp', 'jenis_kelamin', 'jurusan', 'kelas', 'profil_gambar'],
            'wajib' => ['nama', 'no_telp', 'jenis_kelamin', 'jurusan', 'kelas'],
            'sesiNama' => 'nama',
            'adaUsername' => false,   // anggota login memakai NIM
            'adaSandi'   => true,
        ],
    ];

    /** Nilai yang boleh dipakai untuk select/checkbox. */
    public const JENIS_KELAMIN = ['Laki-Laki', 'Perempuan'];

    /** Opsi jurusan (sama dengan form anggota yang sudah ada). */
    public const JURUSAN = [
        'D4 Teknologi Rekayasa Perangkat Lunak',
        'S1 Teknik Informatika',
        'S1 Sistem Informasi',
        'D3 Teknik Komputer',
    ];

    // -----------------------------------------------------------------------
    // Definisi peran
    // -----------------------------------------------------------------------

    /** Peran yang sedang login, berdasarkan isi session. */
    public static function peranSekarang(): ?string
    {
        foreach (self::PERAN as $peran => $def) {
            if (!empty($_SESSION[$def['id']])) {
                return $peran;
            }
        }

        return null;
    }

    private static function def(string $peran): array
    {
        if (!isset(self::PERAN[$peran])) {
            throw new RuntimeException("Peran tidak dikenal: {$peran}");
        }

        return self::PERAN[$peran];
    }

    // -----------------------------------------------------------------------
    // Baca
    // -----------------------------------------------------------------------

    /**
     * Ambil baris profil milik pengguna yang sedang login.
     */
    public static function ambil(PDO $conn, string $peran): ?array
    {
        $def = self::def($peran);
        $id  = $_SESSION[$def['id']] ?? null;

        if ($id === null) {
            return null;
        }

        $stmt = $conn->prepare(
            "SELECT * FROM `{$def['tabel']}` WHERE `{$def['pk']}` = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // -----------------------------------------------------------------------
    // Tulis
    // -----------------------------------------------------------------------

    /**
     * Simpan data profil.
     *
     * Hanya kolom di allowlist peran yang diproses. Nilai di luar
     * allowlist diabaikan diam-diam (tidak error), sehingga kalau ada
     * POST `status=Aktif` dari tangan=FALSE dan tidak ada efeknya.
     *
     * @return array{ok:bool, pesan:string}
     */
    public static function simpan(PDO $conn, string $peran, array $data): array
    {
        $def = self::def($peran);
        $id  = $_SESSION[$def['id']] ?? null;

        if ($id === null) {
            return ['ok' => false, 'pesan' => 'Sesi tidak valid. Silakan masuk kembali.'];
        }

        // Validasi per kolom.
        $bersih = self::validasi($peran, $data);
        if (isset($bersih['__error'])) {
            return ['ok' => false, 'pesan' => $bersih['__error']];
        }

        // Username harus unik di tabelnya sendiri.
        if ($def['adaUsername'] && array_key_exists('username', $bersih)) {
            $bentrok = self::usernameTerpakai($conn, $peran, $bersih['username'], $id);
            if ($bentrok) {
                return ['ok' => false, 'pesan' => "Username '{$bersih['username']}' sudah dipakai. Pilih yang lain."];
            }
        }

        // Susun SET hanya dari kolom yang benar-benar berubah.
        $set = [];
        $arg = [];
        foreach (array_keys($bersih) as $kolom) {
            $set[] = "`{$kolom}` = :{$kolom}";
            $arg[':' . $kolom] = $bersih[$kolom];
        }

        if (!$set) {
            return ['ok' => true, 'pesan' => 'Tidak ada perubahan yang disimpan.'];
        }

        $sql = 'UPDATE `' . $def['tabel'] . '` SET ' . implode(', ', $set)
             . " WHERE `{$def['pk']}` = :id_akun";
        $arg[':id_akun'] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($arg);

        self::sinkronSesi($peran, $bersih);

        return ['ok' => true, 'pesan' => 'Profil berhasil diperbarui.'];
    }

    /**
     * Validasi & bersihkan input. Mengembalikan array kolom->nilai, atau
     * ['__error' => pesan] bila ada yang tidak valid.
     */
    private static function validasi(string $peran, array $data): array
    {
        $def  = self::def($peran);
        $out  = [];

        foreach ($def['kolom'] as $kolom) {
            if (!array_key_exists($kolom, $data)) {
                continue;
            }

            $nilai = is_string($data[$kolom]) ? trim($data[$kolom]) : $data[$kolom];

            switch ($kolom) {
                case 'nama_pemilik':
                case 'nama_petugas':
                case 'nama':
                    $panjang = mb_strlen((string) $nilai);
                    if ($panjang < 3 || $panjang > 100) {
                        return ['__error' => 'Nama harus antara 3 dan 100 karakter.'];
                    }
                    $out[$kolom] = (string) $nilai;
                    break;

                case 'username':
                    // Hanya huruf, angka, titik, underscore, dan strip.
                    if (!preg_match('/^[A-Za-z0-9._-]{3,60}$/', (string) $nilai)) {
                        return ['__error' => 'Username hanya boleh huruf, angka, titik, strip, atau underscore (3-60 karakter).'];
                    }
                    $out[$kolom] = (string) $nilai;
                    break;

                case 'no_telp':
                    $angka = preg_replace('/\D/', '', (string) $nilai);
                    if ($angka === '') {
                        return ['__error' => 'Nomor telepon wajib diisi.'];
                    }
                    if (strlen($angka) < 8 || strlen($angka) > 15) {
                        return ['__error' => 'Nomor telepon tidak valid (8-15 digit).'];
                    }
                    $out[$kolom] = $angka;
                    break;

                case 'jenis_kelamin':
                    if (!in_array($nilai, self::JENIS_KELAMIN, true)) {
                        return ['__error' => 'Jenis kelamin tidak valid.'];
                    }
                    $out[$kolom] = (string) $nilai;
                    break;

                case 'jurusan':
                    if (!in_array($nilai, self::JURUSAN, true)) {
                        return ['__error' => 'Jurusan tidak dikenal.'];
                    }
                    $out[$kolom] = (string) $nilai;
                    break;

                case 'kelas':
                    if (mb_strlen((string) $nilai) > 10) {
                        return ['__error' => 'Kelas maksimal 10 karakter.'];
                    }
                    $out[$kolom] = (string) $nilai;
                    break;

                case 'profil_gambar':
                    // Diisi oleh Profil::unggahFoto() (nama acak) atau oleh
                    // checkbox hapus_foto (null = tidak ada foto).
                    //
                    // null HARUS ikut masuk $out. Kalau dilewati, kolom ini
                    // tidak pernah masuk ke UPDATE sehingga foto lama masih
                    // tertunjuk di database padahal berkasnya sudah dihapus
                    // dari disk -> gambar rusak.
                    $out[$kolom] = ($nilai === null || $nilai === '') ? null : (string) $nilai;
                    break;
            }
        }

        // Kolom wajib tidak boleh kosong.
        foreach ($def['wajib'] as $kolom) {
            if (array_key_exists($kolom, $out) && $out[$kolom] === '') {
                return ['__error' => "Kolom '" . str_replace('_', ' ', $kolom) . "' wajib diisi."];
            }
        }

        return $out;
    }

    /** Apakah username sudah dipakai akun lain di tabel yang sama? */
    private static function usernameTerpakai(PDO $conn, string $peran, string $username, $id): bool
    {
        $def = self::def($peran);

        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM `{$def['tabel']}`
              WHERE username = :u AND `{$def['pk']}` <> :id"
        );
        $stmt->execute([':u' => $username, ':id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah username yang sama juga ada di tabel peran lain.
     *
     * Tidak memblokir, hanya memberi peringatan. Login terpadu memakai satu
     * form, jadi username yang sama di dua tabel jadi ambigu.
     */
    public static function usernameLintasPeran(PDO $conn, string $peran, string $username): array
    {
        $ditemukan = [];

        foreach (self::PERAN as $nama => $def) {
            if ($nama === $peran || !$def['adaUsername']) {
                continue;
            }

            $stmt = $conn->prepare("SELECT COUNT(*) FROM `{$def['tabel']}` WHERE username = :u");
            $stmt->execute([':u' => $username]);

            if ((int) $stmt->fetchColumn() > 0) {
                $ditemukan[] = $def['label'];
            }
        }

        return $ditemukan;
    }

    // -----------------------------------------------------------------------
    // Password
    // -----------------------------------------------------------------------

    /**
     * Ganti password. Butuh password lama sebagai verifikasi.
     *
     * @return array{ok:bool, pesan:string}
     */
    public static function gantiSandi(PDO $conn, string $peran, string $sandiLama, string $sandiBaru, string $konfirmasi): array
    {
        $def = self::def($peran);
        $id  = $_SESSION[$def['id']] ?? null;

        if ($id === null) {
            return ['ok' => false, 'pesan' => 'Sesi tidak valid. Silakan masuk kembali.'];
        }

        if (!$def['adaSandi']) {
            return ['ok' => false, 'pesan' => 'Peran ini tidak memakai password.'];
        }

        if (strlen($sandiBaru) < self::MIN_SANDI) {
            return ['ok' => false, 'pesan' => 'Password baru minimal ' . self::MIN_SANDI . ' karakter.'];
        }

        if ($sandiBaru !== $konfirmasi) {
            return ['ok' => false, 'pesan' => 'Konfirmasi password tidak cocok.'];
        }

        // Ambil password tersimpan untuk verifikasi.
        $stmt = $conn->prepare("SELECT password FROM `{$def['tabel']}` WHERE `{$def['pk']}` = :id");
        $stmt->execute([':id' => $id]);
        $tersimpan = $stmt->fetchColumn();

        if ($tersimpan === false || !self::sandiCocok($sandiLama, (string) $tersimpan)) {
            return ['ok' => false, 'pesan' => 'Password lama salah.'];
        }

        // Simpan sebagai hash. Auth::cocok() sudah mendukung keduanya,
        // jadi ini aman walau tabel lain masih plaintext.
        $stmt = $conn->prepare("UPDATE `{$def['tabel']}` SET password = :p WHERE `{$def['pk']}` = :id");
        $stmt->execute([':p' => password_hash($sandiBaru, PASSWORD_DEFAULT), ':id' => $id]);

        return ['ok' => true, 'pesan' => 'Password berhasil diubah.'];
    }

    /** Bandingkan input dengan password tersimpan (hash atau plaintext). */
    private static function sandiCocok(string $input, string $tersimpan): bool
    {
        if ($tersimpan === '') {
            return false;
        }

        if (str_starts_with($tersimpan, '$2y$') || str_starts_with($tersimpan, '$argon2')) {
            return password_verify($input, $tersimpan);
        }

        return hash_equals($tersimpan, $input);
    }

    // -----------------------------------------------------------------------
    // Foto profil
    // -----------------------------------------------------------------------

    /**
     * Simpan file gambar yang diunggah dan kembalikan nama file yang aman.
     *
     * Validasi berdasarkan ISI file (getimagesize), bukan nama/ekstensi.
     * Nama file dibuat acak, sehingga nama asli dari pengguna tidak pernah
     * menyentuh filesystem (menutup path traversal & double extension).
     *
     * @return array{ok:bool, nama?:string, pesan:string}
     */
    public static function unggahFoto(?array $file): array
    {
        if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'pesan' => 'Tidak ada berkas yang dipilih.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'pesan' => self::pesanErrorUpload((int) $file['error'])];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'pesan' => 'Berkas tidak valid.'];
        }

        if ((int) $file['size'] > self::MAKS_UKURAN) {
            $maks = round(self::MAKS_UKURAN / 1024 / 1024, 1);
            return ['ok' => false, 'pesan' => "Ukuran foto maksimal {$maks} MB."];
        }

        // Validasi isi: harus gambar sungguhan yang didukung.
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !isset(self::TIPE_GAMBAR[$info[2]])) {
            return ['ok' => false, 'pesan' => 'Berkas harus gambar JPG, PNG, atau WebP.'];
        }

        // Cegah gambar "polyglot" yang juga valid sebagai PHP.
        // getimagesize sudah menyinggung, tapi kita perketat lagi: file
        // tidak boleh mengandung tag <?php di 1KB pertama.
        $kepala = (string) file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (stripos($kepala, '<?php') !== false || stripos($kepala, '<?=') !== false) {
            return ['ok' => false, 'pesan' => 'Berkas ditolak karena berisi kode.'];
        }

        $folder = self::folderUpload();
        if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
            return ['ok' => false, 'pesan' => 'Folder upload tidak dapat dibuat.'];
        }
        self::tulisPerlindungan($folder);

        $nama = bin2hex(random_bytes(16)) . '.' . self::TIPE_GAMBAR[$info[2]];

        if (!@move_uploaded_file($file['tmp_name'], $folder . DIRECTORY_SEPARATOR . $nama)) {
            return ['ok' => false, 'pesan' => 'Gagal menyimpan foto.'];
        }

        @chmod($folder . DIRECTORY_SEPARATOR . $nama, 0644);

        return ['ok' => true, 'nama' => $nama, 'pesan' => 'Foto profil berhasil diperbarui.'];
    }

    /**
     * Hapus foto lama. Hanya menerima nama file yang cocok pola acak milik
     * kita, sehingga tidak bisa dipakai untuk menghapus berkas lain.
     */
    public static function hapusFoto(?string $nama): bool
    {
        if (!$nama) {
            return false;
        }

        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/i', $nama)) {
            return false;   // bukan file milik kita - jangan sentuh
        }

        $path = self::folderUpload() . DIRECTORY_SEPARATOR . $nama;

        return is_file($path) && @unlink($path);
    }

    /** Path absolut folder upload foto profil. */
    public static function folderUpload(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Assets'
             . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profil';
    }

    /**
     * Tulis .htaccess yang melarang eksekusi skrip di folder upload.
     * Diabaikan diam-diam bila server tidak mengizinkan.
     */
    private static function tulisPerlindungan(string $folder): void
    {
        $tujuan = $folder . DIRECTORY_SEPARATOR . '.htaccess';

        if (is_file($tujuan)) {
            return;
        }

        $isi = "# Ditulis otomatis oleh App\Services\Profil\n"
             . "# Melarang eksekusi skrip di dalam folder upload.\n"
             . "<FilesMatch \"\\.(php|phtml|php[3-8]|phps|cgi|pl|py|sh|shtml|htaccess)$\">\n"
             . "  Require all denied\n"
             . "</FilesMatch>\n"
             . "Options -ExecCGI -Indexes\n";

        @file_put_contents($tujuan, $isi);
    }

    private static function pesanErrorUpload(int $kode): string
    {
        return match ($kode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran foto melebihi batas server.',
            UPLOAD_ERR_PARTIAL                        => 'Upload tidak lengkap. Coba lagi.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'Folder sementara tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE                     => 'Server gagal menulis berkas.',
            UPLOAD_ERR_EXTENSION                      => 'Upload dihentikan oleh ekstensi PHP.',
            default                                   => 'Upload gagal (kode ' . $kode . ').',
        };
    }

    // -----------------------------------------------------------------------
    // Session
    // -----------------------------------------------------------------------

    /**
     * Samakan session dengan data terbaru supaya sidebar/navbar langsung
     * menampilkan nama & foto yang baru tanpa perlu login ulang.
     */
    public static function sinkronSesi(string $peran, array $perubahan): void
    {
        $def = self::def($peran);

        if (isset($perubahan[$def['sesiNama']])) {
            $_SESSION[$def['sesiNama']] = $perubahan[$def['sesiNama']];
        }

        if (array_key_exists('profil_gambar', $perubahan)) {
            $_SESSION['profil_gambar'] = $perubahan['profil_gambar'];
        }

        if (isset($perubahan['username'])) {
            $_SESSION['username'] = $perubahan['username'];
        }
    }

    /**
     * URL publik foto profil, atau null kalau belum ada.
     * $prefix relatif ke lokasi file pemanggil (mis. '../../').
     */
    public static function urlFoto(?string $nama, string $prefix): ?string
    {
        $nama = trim((string) $nama);

        if ($nama === '') {
            return null;
        }

        // Hanya nama file acak milik kita yang dilayani.
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/i', $nama)) {
            return null;
        }

        return $prefix . 'Assets/uploads/profil/' . $nama;
    }
}
