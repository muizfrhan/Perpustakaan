<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Flatpickr Stylesheet -->
  <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <script src="../../Assets/scripts/header.js" defer></script>
  <link rel="stylesheet" href="../../Assets/css/header.css">
  <link rel="stylesheet" href="../../Assets/css/dashboard.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    .home-section {
      position: relative;
      min-height: 100vh;
      max-width: 91%;
      left: 110px;
      width: calc(100% - 0px);
      transition: all 0.5s ease;
      background: #ffffff;
    }

    .sidebar.open~.home-section {
      left: 260px;
      width: calc(100% - 280px);
    }

    .home-section .text {
      display: inline-block;
      color: #fff;
      font-size: 25px;
      font-weight: 500;
      margin: 18px;
    }

    /* .calendar {
      background: #fff;
      border-radius: 25px;
      width: 700px;
      padding: 20px;
    } */

    .calendar-header h2 {
      font-size: 1.5rem;
      margin: 0;
    }

    .calendar-header button {
      background: #007bff;
      color: #fff;
      border: none;
      padding: 10px 10px;
      border-radius: 100px;
      cursor: pointer;
    }

    .calendar-header button:hover {
      background: #0056b3;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 5px;
      text-align: center;
    }

    .day {
      padding: 10px 12px;
      background: #e9ecef;
      border-radius: 25px;
      transition: background 0.3s ease, transform 0.3s ease;
    }

    .day:hover {
      background: #007bff;
      color: #fff;
      transform: scale(1.1);
    }

    .day-header {
      font-weight: bold;
      color: #495057;
    }

    .current-day {
      background: #007bff;
      color: #fff;
    }

    .autocomplete-item {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      cursor: pointer;
    }

    .autocomplete-item:hover {
      background-color: #f1f1f1;
    }

    #search_results {
      border: 1px solid #ddd;
      max-height: 100px;
      overflow-y: auto;
      display: none;
    }

    #search_results_buku {
      border: 1px solid #ddd;
      border-radius: 4px;
      max-height: 100px;
      overflow-y: auto;
      display: none;
    }
  </style>
</head>

<body>
  <div class="sidebar shadow ms">
    <div class="logo-details mb-4">
      <div class="logo_name">
        <img src="../../Assets/img/LogoPusaku.png" alt="" class="logo-img">
      </div>
      <i class='bx bx-menu' id="btn"></i>
    </div>
    <ul class="nav-list">
      <li>
        <a href="../Dashboard/dashboard.php">
          <i class='bx bx-grid-alt'></i>
          <span class="links_name">Dashboard</span>
        </a>
        <span class="tooltip">Dashboard</span>
      </li>
      <li>
        <a href="../Anggota/anggota.php" class="active">
          <i class='bx bx-user'></i>
          <span class="links_name">Anggota</span>
        </a>
        <span class="tooltip">Anggota</span>
      </li>
      <li>
        <a href="../Buku/buku.php">
          <i class='bx bx-book'></i>
          <span class="links_name">Buku</span>
        </a>
        <span class="tooltip">Buku</span>
      </li>
      <li>
        <a href="../Peminjaman/peminjaman.php">
          <i class='bx bx-book-add'></i>
          <span class="links_name">Peminjaman</span>
        </a>
        <span class="tooltip">Peminjaman</span>
      </li>
      <li>
        <a href="../Pengembalian/pengembalian.php">
          <i class='bx bx-book-bookmark'></i>
          <span class="links_name">Pengembalian</span>
        </a>
        <span class="tooltip">Pengembalian</span>
      </li>
    </ul>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>