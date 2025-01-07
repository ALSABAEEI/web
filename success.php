<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Get the order ID, QR path, and points earned from the URL
$orderID = $_GET['orderID'] ?? null;
$qrPath = $_GET['qrPath'] ?? null;
$pointsEarned = $_GET['pointsEarned'] ?? null; // Get points from URL

// Redirect to orders page if order ID or QR path is missing
if (!$orderID || !$qrPath) {
    header('Location: orders.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <!-- Header -->
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="Dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="membership-card.php">Membership Card</a></li>
                    <li><a href="update-student-info.php">Manage Profile</a></li>
                    <li><a href="orders.php">Orders</a></li>

                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main py-5">
        <div class="container text-center">
            <h2 class="mb-4">Order Completed Successfully!</h2>
            <p class="mb-4">Your order has been placed successfully. You can use the QR code below for reference:</p>
            <img src="<?= htmlspecialchars($qrPath); ?>" alt="QR Code" class="mb-4" style="max-width: 200px;">

            <?php if ($pointsEarned) : ?>
                <p class="alert alert-success">Congratulations! You earned <strong><?= htmlspecialchars($pointsEarned); ?></strong> points for this order. Keep shopping to earn more rewards!</p>
            <?php endif; ?>

            <div class="d-flex justify-content-center gap-3">
                <a href="orders.php" class="btn btn-primary">View Orders</a>
                <a href="addorder.php" class="btn btn-secondary">Add New Order</a>
            </div>
        </div>
    </main>

    <footer id="footer" class="footer bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 RP System. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>