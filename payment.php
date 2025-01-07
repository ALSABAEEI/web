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

// Fetch selected packages and their quantities from the session or a form submission
$selectedPackages = isset($_SESSION['selected_packages']) ? $_SESSION['selected_packages'] : [];

// Fetch package details from the database
$packages = [];
if (!empty($selectedPackages)) {
    $packageIDs = implode(",", array_keys($selectedPackages));
    $packageQuery = "SELECT * FROM package WHERE PackageID IN ($packageIDs)";
    $result = $conn->query($packageQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $packages[] = $row;
        }
    }
}
// Calculate the total price
// Calculate the total price
$totalPrice = 0;
foreach ($packages as $package) {
    $packageID = $package['PackageID'];
    if (isset($selectedPackages[$packageID])) {
        $quantity = $selectedPackages[$packageID]['Quantity']; // Access Quantity
        $totalPrice += $package['Price'] * $quantity;
    }
}

// Fetch membership card balance
$membershipBalance = 0;
$cardID = null;
if (isset($_SESSION['studID'])) {
    $balanceQuery = "SELECT Balance, CardID FROM membershipcard WHERE studID = ?";
    $stmt = $conn->prepare($balanceQuery);
    $stmt->bind_param("i", $_SESSION['studID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cardData = $result->fetch_assoc();
    $membershipBalance = $cardData['Balance'] ?? 0;
    $cardID = $cardData['CardID'] ?? null;
    $stmt->close();
}

// Handle form submission for payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studID = $_SESSION['studID'];
    $currentDate = date('Y-m-d');
    $paymentMethod = $_POST['payment_method'];

    if (isset($_GET['orderID'])) {
        $orderID = (int)$_GET['orderID'];

        // Update the order status
        $updateOrderQuery = "UPDATE orders SET OrderStatus = 'Ordered', Date = ? WHERE OrderID = ?";
        $stmt = $conn->prepare($updateOrderQuery);
        $stmt->bind_param("si", $currentDate, $orderID);
        $stmt->execute();
    } else {
        die("Order ID not provided.");
    }

    $paymentSuccessful = false;

    if ($paymentMethod === "membership_card") {
        if ($membershipBalance < $totalPrice) {
            echo "<div class='alert alert-danger'>Insufficient balance on your membership card.</div>";
        } else {
            $cardQuery = "UPDATE membershipcard SET Balance = Balance - ? WHERE studID = ?";
            $stmt = $conn->prepare($cardQuery);
            $stmt->bind_param("di", $totalPrice, $studID);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $paymentSuccessful = true;

                // Insert transaction into Transactions table
                $transactionQuery = "INSERT INTO transactions (CardID, Type, Amount, Date) VALUES (?, 'Redeem', ?, ?)";
                $stmt = $conn->prepare($transactionQuery);
                $stmt->bind_param("ids", $cardID, $totalPrice, $currentDate);
                $stmt->execute();
            }
        }
    } elseif ($paymentMethod === "cash") {
        $paymentSuccessful = true;
    }

    if ($paymentSuccessful) {
        // Add points to the membership card
        $pointsEarned = round($totalPrice * 0.20, 2);
        $pointsQuery = "UPDATE membershipcard SET Balance = Balance + ? WHERE studID = ?";
        $stmt = $conn->prepare($pointsQuery);
        $stmt->bind_param("di", $pointsEarned, $studID);
        $stmt->execute();

        // Generate QR Code
        $orderDetailsURL = "http://localhost/web/web/gp-1.0.0/orderDetails.php?orderID=$orderID";
        $qrCodePath = "qrcodes/order_$orderID.png";
        if (!is_dir("qrcodes")) {
            mkdir("qrcodes", 0777, true);
        }
        include 'phpqrcode/qrlib.php';
        QRcode::png($orderDetailsURL, $qrCodePath, QR_ECLEVEL_L, 4);

        // Save QR Code path in the database
        $updateQRQuery = "UPDATE orders SET QRCodePath = ? WHERE OrderID = ?";
        $stmt = $conn->prepare($updateQRQuery);
        $stmt->bind_param("si", $qrCodePath, $orderID);
        $stmt->execute();

        // Clear session
        unset($_SESSION['selected_packages']);

        header("Location: success.php?orderID=$orderID&qrPath=$qrCodePath&pointsEarned=$pointsEarned");
        exit();
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RP System</title>
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
                            <h4>Checkout</h4>
                        </div>

                        <?php if (!empty($packages)) : ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Package Name</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($packages as $package) : ?>
                                        <?php
                                        $packageID = $package['PackageID'];
                                        $quantity = $selectedPackages[$packageID]['Quantity'] ?? 0;
                                        $price = $package['Price'];
                                        $subtotal = $price * $quantity; // Calculate subtotal dynamically
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($package['PackageName']); ?></td>
                                            <td>$<?= htmlspecialchars($price); ?></td>
                                            <td><?= htmlspecialchars($quantity); ?></td>
                                            <td>$<?= htmlspecialchars($subtotal); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th>$<?= htmlspecialchars($totalPrice); ?></th>
                                    </tr>
                                </tfoot>
                            </table>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_membership" value="membership_card" required>
                                        <label class="form-check-label" for="payment_membership">
                                            Membership Card (Balance: $<?= number_format($membershipBalance, 2); ?>)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash">
                                        <label class="form-check-label" for="payment_cash">
                                            Cash
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Complete Checkout</button>
                            </form>
                        <?php else : ?>
                            <p class="text-center">No items in the cart.</p>
                        <?php endif; ?>
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
</body>

</html>