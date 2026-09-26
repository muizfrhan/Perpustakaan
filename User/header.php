<?php
session_start();
require_once '../Config/koneksi.php'; // Sesuaikan path dengan struktur folder

if (!isset($_SESSION['nim'])) {
  header('Location: login.php');
  exit;
}

$page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SIMPUS - Digital Reading Club</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css"
    rel="stylesheet" />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    rel="stylesheet" />
  <style>
    :root {
      --primary: #7371fc;
      --background-light: rgba(255, 255, 255, 0.95);
      --text-color: #333;
    }

    body {
      padding-bottom: 100px;
      background-color: #f8f9fa;
      display: flex;
      min-height: 100vh;
      /* Ganti height menjadi min-height */
      margin: 0;
      flex-direction: column;
      /* Tambahkan ini agar elemen bisa di-stack secara vertikal */
    }

    .menu-icon {
      cursor: pointer;
      color: var(--text-color);
      font-size: 24px;
    }

    .hamburger-menu {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background-color: var(--background-light);
      border-radius: 15px 15px 0 0;
      box-shadow: 0 -8px 16px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      z-index: 1000;
      animation: slideUp 0.3s ease;
    }

    .hamburger-menu .menu-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      background-color: var(--primary);
      color: white;
      font-size: 18px;
    }

    .hamburger-menu .menu-header .close-menu {
      cursor: pointer;
      font-size: 20px;
      font-weight: bold;
    }

    .hamburger-menu a {
      display: block;
      padding: 15px 20px;
      text-decoration: none;
      color: var(--text-color);
      font-size: 16px;
      border-bottom: 1px solid #f0f0f0;
      transition: background-color 0.3s ease;
    }

    .hamburger-menu a:hover {
      background-color: #f1f1f1;
      color: var(--primary);
    }

    .menu-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 999;
    }

    .search-bar {
      margin: 20px;
      margin-bottom: 50px;
    }

    .search-bar input {
      border-radius: 5px 0 0 5px;
    }

    .search-bar button {
      background-color: #d2f902;
      border-radius: 0 5px 5px 0;
    }

    .banner {
      background-color: var(--primary);
      border-radius: 10px;
      margin: 20px;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
      height: 150px;
    }

    .banner img {
      transform: scaleX(-1);
      position: relative;
      top: -25px;
      height: 200px;
    }


    .conten {
      margin: 20px;
    }

    .card {
      border: 1px solid #ddd;
      border-radius: 5px;

    }

    .card-body {
      padding: 1rem;
      /* Padding dalam card */
    }

    .btn-sm {
      font-size: 0.8rem;
      /* Ukuran font tombol */
    }

    .card img {
      margin: 0px;
      height: px;
      object-fit: cover;
    }


    .btn-primary {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    .btn-primary:hover {
      background-color: #5c5ae1;
      border-color: #5c5ae1;
    }

    .menu-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 999;
    }

    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      border-top: 1px solid #ddd;
      z-index: 10;
      background-color: rgba(255, 255, 255, 0.8);
      /* Semi-transparent background */
      backdrop-filter: blur(10px);
      /* Efek blur */
    }

    .bottom-nav a {
      color: #888;
      text-decoration: none;
      text-align: center;
      padding: 10px 0;
      flex: 1;
      transition: none !important;
      /* Nonaktifkan transisi yang tidak perlu */
    }


    .bottom-nav a.active {
      color: var(--primary);
      font-weight: bold;
    }

    .bottom-nav.blur {
      backdrop-filter: blur(20px);
      /* Efek blur lebih kuat saat di-scroll */
      background-color: rgba(255, 255, 255, 0.95);
      /* Lebih opaque saat di-scroll */
    }
  </style>
  <!-- Tambahkan di bagian CSS header.php -->
  <style>
    /* ANIMASI UMUM */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes float {
      0% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(-10px);
      }

      100% {
        transform: translateY(0px);
      }
    }

    /* ANIMASI CARD BUKU */
    .card {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: fadeInUp 0.6s ease-out forwards;
      opacity: 0;
      transform: translateY(20px);
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 24px rgba(115, 113, 252, 0.2);
    }

    /* ANIMASI LOADER MODAL */
    #modalContent[loading] {
      position: relative;
      min-height: 200px;
    }

    #modalContent[loading]::after {
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 40px;
      height: 40px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid var(--primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: translate(-50%, -50%) rotate(0deg);
      }

      100% {
        transform: translate(-50%, -50%) rotate(360deg);
      }
    }

    /* ANIMASI BANNER */
    .banner {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: fadeInUp 0.6s ease-out forwards;
    }

    /* ANIMASI MENU HAMBURGER */
    .hamburger-menu a {
      transform: translateX(20px);
      opacity: 0;
      animation: menuItemSlide 0.4s ease-out forwards;
    }

    @keyframes menuItemSlide {
      from {
        opacity: 0;
        transform: translateX(20px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* ANIMASI TOMBOL SEARCH */
    .search-bar button {
      transition: all 0.3s ease;
    }

    .search-bar button:hover {
      transform: scale(1.05) rotate(-5deg);
    }

    /* STAGGER ANIMATION UNTUK CARD */
    .card:nth-child(1) {
      animation-delay: 0.1s;
    }

    .card:nth-child(2) {
      animation-delay: 0.2s;
    }

    .card:nth-child(3) {
      animation-delay: 0.3s;
    }

    .card:nth-child(4) {
      animation-delay: 0.4s;
    }

    .card:nth-child(5) {
      animation-delay: 0.5s;
    }

    .card:nth-child(6) {
      animation-delay: 0.6s;
    }
  </style>


</head>

<body>
  <!-- Header -->
  <header
    class="d-flex justify-content-between align-items-center p-3 border-bottom">
    <div class="logo">
      <img src="../Assets/Img/logo.png" alt="Logo" height="40" />
    </div>
    <div class="menu-icon">
      <i class="fas fa-bars fa-lg"></i>
    </div>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu">
      <div class="menu-header">
        <h5>Kategori</h5>
        <span class="close-menu">&times;</span>
      </div>
      <a href="#">All Categories</a>
      <a href="#">Teori Komunikasi</a>
      <a href="#">Public Relations</a>
      <a href="#">Jurnalistik</a>
      <a href="#">Komunikasi Digital</a>
    </div>
    <div class="menu-overlay"></div>
  </header>
  <!-- Bottom Navigation -->
  <nav class="bottom-nav d-flex justify-content-around">
    <a href="home.php" class="nav-link <?php if ($page == 'home') echo 'active'; ?>" data-id="home">
      <i class="fas fa-home fa-lg"></i>
      <div>Beranda</div>
    </a>
    <a href="history.php" class="nav-link <?php if ($page == 'history') echo 'active'; ?>" data-id="history">
      <i class="fas fa-book fa-lg"></i>
      <div>History</div>
    </a>
    <a href="account.php" class="nav-link <?php if ($page == 'akun') echo 'active'; ?>" data-id="akun">
      <i class="fas fa-user fa-lg"></i>
      <div>Akun</div>
    </a>
  </nav>


  <script>
    // Elements
    const menuIcon = document.querySelector(".menu-icon");
    const hamburgerMenu = document.querySelector(".hamburger-menu");
    const menuOverlay = document.querySelector(".menu-overlay");
    const closeMenu = document.querySelector(".close-menu");

    // Show menu
    menuIcon.addEventListener("click", () => {
      hamburgerMenu.style.display = "block";
      menuOverlay.style.display = "block";
    });

    // Hide menu
    menuOverlay.addEventListener("click", () => {
      hamburgerMenu.style.display = "none";
      menuOverlay.style.display = "none";
    });

    closeMenu.addEventListener("click", () => {
      hamburgerMenu.style.display = "none";
      menuOverlay.style.display = "none";
    });

    // Change active link in bottom navigation
    const navLinks = document.querySelectorAll(".bottom-nav .nav-link");
    const currentPage = window.location.pathname.split('/').pop();

    navLinks.forEach((link) => {
      if (link.href.includes(currentPage)) {
        link.classList.add("active");
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Tambahkan di JavaScript header.php -->
  <script>
    // Animasi saat membuka menu hamburger
    menuIcon.addEventListener("click", () => {
      hamburgerMenu.style.display = "block";
      menuOverlay.style.display = "block";

      // Animate menu items
      const menuItems = document.querySelectorAll('.hamburger-menu a');
      menuItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.1}s`;
      });
    });

    // Animasi scroll untuk card
    window.addEventListener('scroll', () => {
      const cards = document.querySelectorAll('.card');
      cards.forEach(card => {
        const cardTop = card.getBoundingClientRect().top;
        if (cardTop < window.innerHeight * 0.8) {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }
      });
    });

    // Animasi untuk loading modal
    function loadDetailForm(kodeBuku) {
      const modalContent = document.getElementById('modalContent');
      modalContent.setAttribute('loading', '');

      fetch(`get_detail_buku.php?kode_buku=${kodeBuku}`)
        .then(response => response.json())
        .then(data => {
          modalContent.removeAttribute('loading');
          // ... kode existing ...
        });
    }
  </script>