<?php
/**
 * Halaman Akun Anggota - sekarang BISA diedit.
 *
 * Sebelumnya halaman ini hanya menampilkan profil (read-only). Sekarang
 * anggota bisa mengubah foto, nama, telepon, jenis kelamin, jurusan,
 * kelas, dan password lewat form yang sama dengan Admin & Owner.
 *
 * Kolom yang TIDAK bisa diubah dari sini: nim (identitas login),
 * tgl_lahir, dan status_mhs. Keduanya sengaja tidak ada di allowlist
 * App\Services\Profil - status_mhs khususnya akan menjadi celah eskalasi
 * privilege (anggota bisa mengaktifkan dirinya sendiri).
 */
require_once __DIR__ . '/../Config/bootstrap.php';
require_once __DIR__ . '/../Config/koneksi.php';

require_user();

$peranProfil      = 'anggota';
$urlPrefix = '../';

include __DIR__ . '/header.php';

require __DIR__ . '/../Config/partials/profil.php';

include __DIR__ . '/footer.php';
