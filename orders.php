<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "rapidprint");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in and has a `studID` in session
if (!isset($_SESSION['studID'])) {
    header('Location: login.php');
    exit();
}

$studID = $_SESSION['studID'];

// Handle "Pay Order" action to populate session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_order'])) {
    $orderID = (int)$_POST['pay_order'];

    // Fetch the order details and order lines
    $orderQuery = "SELECT * FROM orders WHERE OrderID = ? AND studID = ?";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("ii", $orderID, $studID);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        // Fetch associated order lines
        $orderLinesQuery = "SELECT ol.PackageID, ol.SubPrice, p.Price 
                            FROM order_line ol 
                            JOIN package p ON ol.PackageID = p.PackageID 
                            WHERE ol.Order_ID = ?";
        $stmt = $conn->prepare($orderLinesQuery);
        $stmt->bind_param("i", $orderID);
        $stmt->execute();
        $result = $stmt->get_result();

        // Reset session variable for selected packages
        $_SESSION['selected_packages'] = [];

        while ($line = $result->fetch_assoc()) {
            // Calculate quantity from SubPrice and Price
            $quantity = $line['SubPrice'] / $line['Price'];
            $_SESSION['selected_packages'][$line['PackageID']] = [
                'Quantity' => $quantity,
                'SubPrice' => $line['SubPrice']
            ];
        }

        // Redirect to payment page with orderID
        header("Location: payment.php?orderID=$orderID");
        exit();
    } else {
        die("Order not found or you do not have permission to pay for this order.");
    }
}

// Delete order
if (isset($_POST['delete_order'])) {
    $orderID = (int)$_POST['delete_order'];

    // Delete related records in order_line
    $deleteOrderLineQuery = "DELETE FROM order_line WHERE Order_ID = ?";
    $stmt = $conn->prepare($deleteOrderLineQuery);
    $stmt->bind_param("i", $orderID);
    $stmt->execute();

    // Delete the order
    $deleteOrderQuery = "DELETE FROM orders WHERE OrderID = ?";
    $stmt = $conn->prepare($deleteOrderQuery);
    $stmt->bind_param("i", $orderID);
    $stmt->execute();

    header("Location: orders.php?success=Order deleted successfully");
    exit();
}

// Fetch user orders
$userOrders = [];
$orderQuery = "SELECT o.OrderID, o.OrderTotal, o.OrderStatus, o.Upload_File, o.Date, o.QRCodePath
               FROM orders o
               WHERE o.studID = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("i", $studID);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $userOrders[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
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
    <main class="main bg-light py-5">
        <div class="container">
            <h2 class="mb-4">Manage Your Orders</h2>

            <!-- Success Message -->
            <?php if (isset($_GET['success'])) : ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Add Order Button -->
            <div class="mb-3">
                <a href="addorder.php" class="btn btn-primary">Add Order</a>
            </div>

            <!-- List of Orders -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>File Name</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userOrders as $order) : ?>
                        <tr>
                            <td><?= htmlspecialchars($order['OrderID']); ?></td>
                            <td><?= htmlspecialchars(basename($order['Upload_File'])); ?></td>
                            <td>$<?= htmlspecialchars($order['OrderTotal']); ?></td>
                            <td><?= htmlspecialchars($order['OrderStatus']); ?></td>
                            <td><?= htmlspecialchars($order['Date']); ?></td>
                            <td>
                                <?php if (!empty($order['QRCodePath'])) : ?>
                                    <img src="<?= htmlspecialchars($order['QRCodePath']); ?>" alt="QR Code" style="max-width: 100px;">
                                <?php else : ?>
                                    No QR Code
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= htmlspecialchars($order['Upload_File']); ?>" target="_blank" class="btn btn-info btn-sm">View File</a>
                                <?php if (strtolower($order['OrderStatus']) !== 'ordered') : ?>
                                    <a href="editOrder.php?orderID=<?= $order['OrderID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $order['OrderID']; ?>)">Delete</button>
                                <?php if (strtolower($order['OrderStatus']) === 'unpaid') : ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="pay_order" value="<?= $order['OrderID']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Pay Order</button>
                                    </form>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        function confirmDelete(orderID) {
            if (confirm('Are you sure you want to delete this order?')) {
                // Dynamically create a form for deletion
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = ''; // Current page

                // Create a hidden input for the order ID
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_order';
                input.value = orderID;

                // Append the input to the form and submit
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>