<?php
require_once '../Config/koneksi.php';
include 'header.php';

$nim = $_SESSION['nim'];

try {
    $query = $conn->prepare("SELECT * FROM anggota WHERE nim = :nim");
    $query->bindParam(':nim', $nim);
    $query->execute();
    $mhs = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!-- Konten Akun -->
<section class="conten mt-4">
    <div class="container">
        <!-- Header Profil -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="avatar-wrapper position-relative mx-auto">
                        <div class="">
                            <img class="avatar-circle" src="../Assets/Anime Date/anime5.jpg" alt="">
                        </div>
                        <span class="status-dot <?= $mhs['status_mhs'] === 'Aktif' ? 'bg-success' : 'bg-secondary' ?>"></span>
                    </div>
                </div>
                <div class="col-md-9 mt-3 mt-md-0">
                    <h1 class="profile-name text-center mb-2"><?= $mhs['nama'] ?></h1>
                    <p class="me-2 text-center"><?= $mhs['nim'] ?></p>
                </div>
            </div>
        </div>

        <!-- Grid Informasi -->
        <div class="row g-4">
            <!-- Kolom Kiri -->
            <div class="col-lg-8">
                <!-- Card Informasi Pribadi -->
                <div class="card info-card shadow-hover">
                    <div class="card-header bg-transparent border-bottom">
                        <h3 class="mb-0"><i class="fas fa-user-circle me-2 text-primary"></i>Informasi Pribadi</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="info-list">
                                    <dt><i class="fas fa-venus-mars me-2"></i>Jenis Kelamin</dt>
                                    <dd><?= $mhs['jenis_kelamin'] ?></dd>

                                    <dt><i class="fas fa-birthday-cake me-2"></i>Tanggal Lahir</dt>
                                    <dd><?= date('d F Y', strtotime($mhs['tgl_lahir'])) ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="info-list">
                                    <dt><i class="fas fa-phone me-2"></i>Telepon</dt>
                                    <dd><?= $mhs['no_telp'] ?: '-' ?></dd>

                                    <dt><i class="fas fa-user-shield me-2"></i>Status</dt>
                                    <dd>
                                        <span class="badge <?= $mhs['status_mhs'] === 'Aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $mhs['status_mhs'] ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Custom Styles */
    .avatar-wrapper {
        width: 150px;
        position: relative;
    }

    .avatar-circle {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: white;
        background: linear-gradient(135deg, var(--primary) 0%, #918efa 100%);
        box-shadow: 0 8px 24px rgba(115, 113, 252, 0.2);
    }

    .status-dot {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 3px solid white;
        position: absolute;
        bottom: 10px;
        right: 10px;
    }

    .profile-name {
        font-size: 2.2rem;
        font-weight: 700;
        color: var(--text-color);
    }

    .info-card,
    .security-card,
    .stats-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .shadow-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    }

    .info-list dt {
        color: #6c757d;
        font-weight: 500;
        margin-top: 1rem;
    }

    .info-list dd {
        color: var(--text-color);
        font-size: 1.05rem;
    }

    .security-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .progress {
        border-radius: 10px;
        background-color: #f0f0f0;
    }

    .toggle-password {
        cursor: pointer;
        background-color: #f8f9fa;
        transition: color 0.3s ease;
    }

    .toggle-password:hover {
        color: var(--primary);
    }

    @media (max-width: 768px) {
        .profile-name {
            font-size: 1.8rem;
        }

        .avatar-circle {
            width: 120px;
            height: 120px;
            font-size: 3rem;
        }
    }
</style>

<script>
    // Toggle Password Visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    // Form Validation
    document.getElementById('formUbahPassword').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = {
            passwordLama: document.getElementById('passwordLama').value,
            passwordBaru: document.getElementById('passwordBaru').value,
            konfirmasiPassword: document.getElementById('konfirmasiPassword').value
        };

        // Validasi Client-side
        if (formData.passwordBaru.length < 8) {
            showAlert('danger', 'Password harus minimal 8 karakter!');
            return;
        }

        if (formData.passwordBaru !== formData.konfirmasiPassword) {
            showAlert('danger', 'Konfirmasi password tidak sesuai!');
            return;
        }

        // Submit AJAX
        fetch('ubah_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nim: <?= $nim ?>,
                    ...formData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Password berhasil diperbarui!');
                    $('#ubahPasswordModal').modal('hide');
                } else {
                    showAlert('danger', data.message || 'Gagal memperbarui password');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Terjadi kesalahan sistem');
            });
    });

    function showAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.role = 'alert';
        alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

        document.querySelector('.modal-body').prepend(alert);

        setTimeout(() => alert.remove(), 5000);
    }
</script>