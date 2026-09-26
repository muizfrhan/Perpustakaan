<?php
session_start();
include '../Config/koneksi.php'; // Pastikan path file config benar

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = $_POST['nim'];
    $password = $_POST['password'];

    try {
        // Query untuk memeriksa data di tabel anggota
        $stmt = $conn->prepare("SELECT nim, password, nama FROM anggota WHERE nim = :nim");
        $stmt->bindParam(':nim', $nim, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi password
            if ($password === $user['password']) { // Ganti dengan password_verify jika menggunakan hash
                $_SESSION['nim'] = $user['nim'];
                $_SESSION['nama'] = $user['nama'];

                header('Location: home.php'); // Ganti dengan halaman utama setelah login
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'NIM tidak ditemukan!';
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login User</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
        rel="stylesheet" />
    <link rel="stylesheet" href="../Assets/css/loginuser.css" />
</head>

<body>
    <div class="login-container">
        <div class="login-logo mt-4 mb-4">
            <img src="../Assets/img/logorev.svg" alt="University Logo" />
        </div>
        <h2 class="login-header text-center">Welcome Back!</h2>
        <p class="login-subtitle text-center">Please login to your account</p>

        <!-- Tampilkan pesan error -->
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="nim" class="form-label">NIM</label>
                <input
                    type="text"
                    name="nim"
                    class="form-control"
                    id="nim"
                    placeholder="Nim"
                    required />
            </div>
            <div class="mb-3 mt-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        id="password"
                        placeholder="Password"
                        required />
                    <button class="btn btn-outline-warning" type="button" id="showPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-4 mb-4">Login</button>
        </form>
        <div class="text-center">
            <p class="mt-3">
                Don't have an account?
                <a href="signup.php" class="signup-link">Sign-Up</a>
            </p>
        </div>
    </div>
    <script>
        const showPassword = document.getElementById("showPassword");
        const passwordInput = document.getElementById("password");
        showPassword.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                showPassword.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = "password";
                showPassword.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    </script>
</body>