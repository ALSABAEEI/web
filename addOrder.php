<?php
session_start();

if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

// Fetch available branches
$branches = [];
$branchQuery = "SELECT * FROM branch";
$result = $conn->query($branchQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = "uploaded_file/";
    $studID = $_SESSION['studID'];
    $branchID = $_POST['branchID']; // Selected branch ID
    $currentDate = date('Y-m-d'); // Get the current date in 'YYYY-MM-DD' format

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadFile = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadFile = $uploadDir . basename($_FILES['document']['name']);
        move_uploaded_file($_FILES['document']['tmp_name'], $uploadFile);
    }

    // Insert the order into the database, including branch ID
    $orderQuery = "INSERT INTO orders (studID, BranchID, OrderStatus, Upload_File, OrderTotal, Date) VALUES (?, ?, 'Unpaid', ?, 0, ?)";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("iiss", $studID, $branchID, $uploadFile, $currentDate);
    $stmt->execute();

    $orderID = $stmt->insert_id;
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

    $updateOrderQuery = "UPDATE orders SET OrderTotal = ? WHERE OrderID = ?";
    $stmt = $conn->prepare($updateOrderQuery);
    $stmt->bind_param("di", $totalPrice, $orderID);
    $stmt->execute();

    // Populate session for payment
    $_SESSION['selected_packages'] = [];
    foreach ($_POST['order_lines'] as $line) {
        $_SESSION['selected_packages'][$line['packageID']] = [
            'Quantity' => $line['quantity']
        ];
    }

    header("Location: payment.php?orderID=$orderID");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Order - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
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
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="card shadow-lg p-4">
                        <div class="text-center mb-3">
                            <h4>Add New Order</h4>
                        </div>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="branchID" class="form-label">Select Branch</label>
                                <select id="branchID" name="branchID" class="form-select" required>
                                    <option value="">Choose a branch...</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['BranchID']; ?>">
                                            <?= htmlspecialchars($branch['BranchName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="document" class="form-label">Upload Document</label>
                                <input type="file" id="document" name="document" class="form-control" required>
                            </div>

                            <div id="orderLines">
                                <div class="mb-3 order-line">
                                    <label for="packageID" class="form-label">Select Package</label>
                                    <select name="order_lines[0][packageID]" class="form-select mb-2" required>
                                        <option value="">Choose a package...</option>
                                        <?php foreach ($packages as $package): ?>
                                            <option value="<?= $package['PackageID']; ?>">
                                                <?= htmlspecialchars($package['PackageName']) . " - $" . htmlspecialchars($package['Price']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" name="order_lines[0][quantity]" class="form-control mb-2" required>
                                </div>
                            </div>

                            <button type="button" id="addOrderLine" class="btn btn-secondary mb-3">Add More</button>
                            <button type="submit" class="btn btn-primary">Submit Order</button>
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