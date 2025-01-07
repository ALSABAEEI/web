<?php
session_start();

// Check if the user is logged in as a student
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch order ID to edit
$orderID = isset($_GET['orderID']) ? (int)$_GET['orderID'] : 0;

// Fetch the order details
$orderQuery = "SELECT * FROM orders WHERE OrderID = ? AND studID = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $orderID, $_SESSION['studID']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or you do not have permission to edit this order.");
}

// Fetch order line details
$orderLines = [];
$orderLineQuery = "SELECT * FROM order_line WHERE Order_ID = ?";
$stmt = $conn->prepare($orderLineQuery);
$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orderLines[] = $row;
}

// Fetch available packages
$packages = [];
$packageQuery = "SELECT * FROM package";
$result = $conn->query($packageQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $packages[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = "uploaded_file/";
    $studID = $_SESSION['studID'];
    $currentDate = date('Y-m-d');

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadFile = $order['Upload_File'];
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadFile = $uploadDir . basename($_FILES['document']['name']);
        move_uploaded_file($_FILES['document']['tmp_name'], $uploadFile);
    }

    // Update the `orders` table
    $updateOrderQuery = "UPDATE orders SET Upload_File = ?, Date = ? WHERE OrderID = ?";
    $stmt = $conn->prepare($updateOrderQuery);
    $stmt->bind_param("ssi", $uploadFile, $currentDate, $orderID);
    $stmt->execute();

    // Delete existing order lines
    $deleteOrderLineQuery = "DELETE FROM order_line WHERE Order_ID = ?";
    $stmt = $conn->prepare($deleteOrderLineQuery);
    $stmt->bind_param("i", $orderID);
    $stmt->execute();

    // Add updated order lines
    $totalPrice = 0;
    foreach ($_POST['order_lines'] as $line) {
        $packageID = $line['packageID'];
        $quantity = $line['quantity'];

        $priceQuery = "SELECT Price FROM package WHERE PackageID = ?";
        $stmt = $conn->prepare($priceQuery);
        $stmt->bind_param("i", $packageID);
        $stmt->execute();
        $priceResult = $stmt->get_result()->fetch_assoc();
        $price = $priceResult['Price'];

        $subPrice = $price * $quantity;
        $totalPrice += $subPrice;

        $orderLineQuery = "INSERT INTO order_line (Order_ID, PackageID, SubPrice) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($orderLineQuery);
        $stmt->bind_param("iid", $orderID, $packageID, $subPrice);
        $stmt->execute();
    }

    // Update total price in the `orders` table
    $updateOrderTotalQuery = "UPDATE orders SET OrderTotal = ? WHERE OrderID = ?";
    $stmt = $conn->prepare($updateOrderTotalQuery);
    $stmt->bind_param("di", $totalPrice, $orderID);
    $stmt->execute();

    header("Location: orders.php?success=Order updated successfully");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="#" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card shadow-lg p-4">
                        <div class="text-center mb-3">
                            <h4>Edit Order</h4>
                        </div>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="document" class="form-label">Upload Document</label>
                                <input type="file" id="document" name="document" class="form-control">
                                <small>Current file: <?= htmlspecialchars(basename($order['Upload_File'])); ?></small>
                            </div>

                            <div id="orderLines">
                                <?php foreach ($orderLines as $index => $line): ?>
                                    <div class="mb-3 order-line">
                                        <label for="packageID" class="form-label">Select Package</label>
                                        <select name="order_lines[<?= $index; ?>][packageID]" class="form-select mb-2" required>
                                            <option value="">Choose a package...</option>
                                            <?php foreach ($packages as $package): ?>
                                                <option value="<?= $package['PackageID']; ?>" <?= $package['PackageID'] == $line['PackageID'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($package['PackageName']) . " - $" . htmlspecialchars($package['Price']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" name="order_lines[<?= $index; ?>][quantity]" class="form-control mb-2" value="<?= htmlspecialchars($line['SubPrice'] / $packages[array_search($line['PackageID'], array_column($packages, 'PackageID'))]['Price']); ?>" required>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" id="addOrderLine" class="btn btn-secondary mb-3">Add More</button>
                            <button type="submit" class="btn btn-primary">Update Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer id="footer" class="footer bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 RP System. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('addOrderLine').addEventListener('click', () => {
            const orderLines = document.getElementById('orderLines');
            const newIndex = orderLines.children.length;

            const newOrderLine = document.createElement('div');
            newOrderLine.classList.add('order-line', 'mb-3');
            newOrderLine.innerHTML = `
        <label for="packageID" class="form-label">Select Package</label>
        <select name="order_lines[${newIndex}][packageID]" class="form-select mb-2" required>
            <option value="">Choose a package...</option>
            <?php foreach ($packages as $package): ?>
                <option value="<?= $package['PackageID']; ?>">
                    <?= htmlspecialchars($package['PackageName']) . " - $" . htmlspecialchars($package['Price']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="quantity" class="form-label">Quantity</label>
        <input type="number" name="order_lines[${newIndex}][quantity]" class="form-control mb-2" required>
    `;
            orderLines.appendChild(newOrderLine);
        });
    </script>
</body>

</html>