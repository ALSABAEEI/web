<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the order ID from the URL
$orderID = isset($_GET['orderID']) ? intval($_GET['orderID']) : null;
if (!$orderID) {
    die("Invalid order ID.");
}

// Fetch order details
$orderQuery = "SELECT o.*, b.BranchName 
               FROM orders o
               LEFT JOIN branch b ON o.BranchID = b.BranchID
               WHERE o.OrderID = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $orderID);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Order not found.");
}
$order = $orderResult->fetch_assoc();

// Fetch order lines
$orderLinesQuery = "SELECT ol.*, p.PackageName, p.Price 
                    FROM order_line ol
                    JOIN package p ON ol.PackageID = p.PackageID
                    WHERE ol.Order_ID = ?";
$stmt = $conn->prepare($orderLinesQuery);
$stmt->bind_param("i", $orderID);
$stmt->execute();
$orderLinesResult = $stmt->get_result();

$orderLines = [];
while ($line = $orderLinesResult->fetch_assoc()) {
    $orderLines[] = $line;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main py-5">
        <div class="container">
            <h2>Order Details</h2>
            <div class="card shadow-sm p-4">
                <div class="text-end">
                </div>
                <h4>Order ID: <?= htmlspecialchars($order['OrderID']); ?></h4>
                <p><strong>Branch:</strong> <?= htmlspecialchars($order['BranchName'] ?? 'N/A'); ?></p>
                <p><strong>Date:</strong> <?= htmlspecialchars($order['Date']); ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($order['OrderStatus']); ?></p>
                <p><strong>Total:</strong> $<?= htmlspecialchars($order['OrderTotal']); ?></p>
                <?php if (!empty($order['Upload_File'])): ?>
                    <p><strong>Uploaded File:</strong> <a href="<?= htmlspecialchars($order['Upload_File']); ?>" target="_blank">View File</a></p>
                <?php endif; ?>
                <hr>
                <h5>Order Items</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderLines as $line): ?>
                            <tr>
                                <td><?= htmlspecialchars($line['PackageName']); ?></td>
                                <td>$<?= htmlspecialchars($line['Price']); ?></td>
                                <td><?= htmlspecialchars($line['SubPrice'] / $line['Price']); ?></td>
                                <td>$<?= htmlspecialchars($line['SubPrice']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="text-end mt-3">
                    <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2025 RP System. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>