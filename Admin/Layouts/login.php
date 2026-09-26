<?php
include '../../config/koneksi.php'; // Menghubungkan ke database menggunakan PDO

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Query untuk validasi login admin menggunakan PDO
        $query = "SELECT id_petugas, nama_petugas FROM petugas WHERE username = :username AND password = :password";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Login berhasil
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            session_start();
            $_SESSION['id_petugas'] = $data['id_petugas']; // Simpan ID petugas di sesi
            $_SESSION['nama_petugas'] = $data['nama_petugas']; // Simpan nama petugas di sesi
            header('Location: ../Dashboard/dashboard.php'); // Redirect ke dashboard admin
            exit;
        } else {
            // Login gagal
            $error = "Username atau password salah.";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../Assets/css/login.css">
    <style>
        .password-toggle {
            position: absolute;
            top: 75%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <h2 class="fw-bold text-center">Login Admin</h2>
            <p class="text-center mb-5">Masukkan username dan password Anda untuk masuk.</p>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center">
                    <?= $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan username Anda" required>
                </div>
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan password Anda" required>
                    <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                </div>
                <button type="submit" class="mt-3 btn btn-primary w-100">Login</button>
            </form>
        </div>
        <div class="graphic-side">
            <img src="../../Assets/img/logorev.svg" alt="Logo" class="img-fluid logo-style">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>

</html>