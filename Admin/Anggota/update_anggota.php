<?php
require_once __DIR__ . '/../../Config/bootstrap.php';
require_once __DIR__ . '/../../Config/koneksi.php';

// Wajib login: halaman ini memuat/mengubah data perpustakaan.
require_admin();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    $nama = $_POST['nama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $kelas = $_POST['kelas'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $jurusan = $_POST['jurusan'];
    $status_mhs = $_POST['status_mhs'];
    $no_telp = $_POST['no_telp'];
    $password = $_POST['password'] ?? ''; // Ambil password baru jika ada

    // Validasi input
    if (empty($nim) || empty($nama) || empty($jenis_kelamin) || empty($kelas) || empty($tgl_lahir) || empty($jurusan)) {
        flash('danger', 'Harap isi semua data.');
        header('Location: anggota.php');
        exit;
    }

    try {
        // Gunakan password baru jika diisi, atau tetap gunakan password lama
        if (!empty($password)) {
            $final_password = $password; // Gunakan password baru
        } else {
            // Jika password tidak diubah, ambil password lama
            $stmt = $conn->prepare("SELECT password FROM anggota WHERE nim = ?");
            $stmt->execute([$nim]);
            $final_password = $stmt->fetchColumn();
        }

        // Update data anggota
        $stmt = $conn->prepare("UPDATE anggota 
                                SET nama = ?, jenis_kelamin = ?, kelas = ?, tgl_lahir = ?, jurusan = ?, status_mhs = ?, no_telp = ?, password = ? 
                                WHERE nim = ?");
        $stmt->execute([$nama, $jenis_kelamin, $kelas, $tgl_lahir, $jurusan, $status_mhs, $no_telp, $final_password, $nim]);

        flash('success', 'Data berhasil diperbarui.');
        header('Location: anggota.php');
        exit;
    } catch (PDOException $e) {
        flash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        header('Location: anggota.php');
        exit;
    }
}

