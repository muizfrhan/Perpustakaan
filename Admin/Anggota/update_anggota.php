<?php
require_once '../../Config/koneksi.php';

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
        echo "<script>
                alert('Harap isi semua data.');
                window.history.back();
              </script>";
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

        echo "<script>
                alert('Data berhasil diperbarui.');
                window.location.href = 'anggota.php'; // Redirect ke halaman anggota
              </script>";
    } catch (PDOException $e) {
        echo "<script>
                alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
}
