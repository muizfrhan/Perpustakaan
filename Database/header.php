<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Perpustakaan - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
<style>
          :root {
        --primary: #7371fc;
        --background-light: rgba(255, 255, 255, 0.95);
        --text-color: #333;
        --yellow: #ffc107;
        --primary-light: #6610f2;
        --primary-light-1: #a594f9;
        --primary-light-2: #cdc1ff;
        --primary-light-3: #e5d9f2;
        --primary-light-4: #f5efff;
      }
    body {
        display: flex;
        height: 100vh;
        overflow: auto;
    }

    .sidebar {
    position: fixed; /* Mengubah posisi menjadi fixed */
    top: 0; /* Menempatkan di bagian atas */
    left: 0; /* Menempatkan di sisi kiri */
    height: 100%; /* Mengatur tinggi sidebar 100% dari viewport */
    min-width: 250px; /* Lebar minimum */
    max-width: 250px; /* Lebar maksimum */
    background-color: var(--primary); /* Warna latar belakang */
    color: #fff; /* Warna teks */
    transition: all 0.3s; /* Transisi */
    overflow-y: auto; /* Memungkinkan scroll jika konten sidebar melebihi tinggi */
}

    .sidebar a {
        text-decoration: none;
        color: #fff;
        padding: 10px 20px;
        display: block;
        border-radius: 4px;
    }

    .sidebar a:hover {
        background-color: #5a56d7; /* Darker purple */
        color: #fff;
    }

    .sidebar .active {
        background-color: #5a56d7;
        color: #fff;
    }

    .sidebar .sidebar-header {
        font-size: 1.2rem;
        text-align: center;
        padding: 20px 0;
        background-color: var(--primary-light);
    }

    .sidebar-header img {
        max-width: 80%; /* Membatasi ukuran logo maksimal */
        height: auto;   /* Menjaga proporsi gambar */
        margin-bottom: 20px; /* Jarak bawah dengan teks */
    }

    .content {
    margin-left: 250px; /* Memberikan jarak yang sama dengan lebar sidebar */
    display: flex;
    flex-direction: column;
    overflow-y: auto; /* Memungkinkan scroll pada konten */
    height: 100vh; /* Mengatur tinggi konten 100% dari viewport */
}


    .profile-info {
        display: flex;
        align-items: center;
    }

    .profile-info img {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        margin-right: 10px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            left: -250px;
            top: 0;
            height: 100%;
            z-index: 1000;
        }

        .sidebar.show {
            left: 0;
        }


    }

    .toggle-btn {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 1100;
    }
</style>

</head>
<body>
    <button class="btn btn-light toggle-btn d-md-none">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar d-flex flex-column">
        <div class="sidebar-header">
            <img src="../Assets/img/logorev.svg" alt="">
        </div>
        <a href="../Public/index.php" class="active">
            <i class="fas fa-home me-2"></i> Dashboard
        </a>
        <a href="../Public/anggota.php">
            <i class="fas fa-users me-2"></i> Anggota
        </a>
        <a href="../Public/buku.php">
            <i class="fas fa-book me-2"></i> Buku
        </a>
        <a href="../Public/peminjaman.php">
            <i class="fas fa-book-reader me-2"></i> Peminjaman
        </a>
        <a href="#">
            <i class="fas fa-undo-alt me-2"></i> Pengembalian
        </a>
        <a href="logout.php" id="logoutButton">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>


    
    <script>
        const toggleBtn = document.querySelector('.toggle-btn');
        const sidebar = document.querySelector('.sidebar');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        document.getElementById('logoutButton').addEventListener('click', function (event) {
            event.preventDefault();
            const userConfirm = confirm("Apakah Anda yakin ingin logout?");
            if (userConfirm) {
                window.location.href = this.href;
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<td>
  <?php if ($row['status_pinjam'] == 'Dipinjam') { ?>
    <span class="badge rounded-4" style="background-color: #fff8e1; color: #ffc107;  padding: 10px 10px; font-weight: bold; display: inline-block; width: 120px; height:28px;  text-align: center;">Dipinjam</span>
  <?php } else { ?>
    <span class="badge rounded-4" style="background-color:  #e8f8e8; color: #38c172; padding: 10px 10px; font-weight: bold; display: inline-block; width: 120px; height:28px; text-align: center;">Dikembalikan</span>
  <?php } ?>
</td>