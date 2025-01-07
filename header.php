<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get the admin's name from the session
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "Admin";
?>

<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <!-- Logo -->
    <a href="dashboard.php" class="logo">
      RP<span>.</span>
    </a>

    <!-- Admin Details -->
    <div class="d-flex align-items-center">
      <div class="admin-info text-end me-3">
        <span class="d-block" style="font-weight: bold;">Welcome, <?php echo htmlspecialchars($adminName); ?>!</span>
        <small style="color: #adb5bd;">Manage your dashboard</small>
      </div>
      <img src="assets/img/admin-avatar.jpg" alt="Admin Avatar" class="rounded-circle" style="width: 40px; height: 40px;">
    </div>

    <!-- Logout Button -->
    <a href="adminDash.php?logout=true" class="btn btn-logout">Log Out</a>
  </div>
</header>

<style>
  .header {
    background-color: #343a40;
    color: #ffffff;
    padding: 10px 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .header .logo {
    color: #ffffff;
    font-weight: bold;
    font-size: 1.5rem;
    text-decoration: none;
  }

  .header .admin-info {
    color: #f8f9fa;
  }

  .header .btn-logout {
    background-color: transparent;
    border: 1px solid #ffc107;
    color: #ffc107;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .header .btn-logout:hover {
    background-color: #ffc107;
    color: #343a40;
  }
</style>
