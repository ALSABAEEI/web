<?php
session_start();

if (!isset($_SESSION['studID']) || empty($_SESSION['studID'])) {
    header('Location: login.php');
    exit();
}

include('phpqrcode/qrlib.php'); // Include the phpqrcode library

$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$studID = $_SESSION['studID'] ?? null;
$message = "";
$card = null;
$qrDirectory = "qrcodes/"; // Directory to store QR codes

// Ensure QR directory exists
if (!file_exists($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

// Fetch membership card details
if ($studID) {
    $query = "SELECT * FROM MembershipCard WHERE studID = $studID";
    $result = mysqli_query($conn, $query);
    $card = mysqli_fetch_assoc($result);
}

// Function to regenerate QR code
function regenerateQRCode($studID, $balance, $qrDirectory, $cardID, $conn) {
    $qrValue = "Student ID: $studID, Balance: " . number_format($balance, 2);
    $qrFilePath = $qrDirectory . "card_" . $studID . ".png";

    // Generate the new QR code
    QRcode::png($qrValue, $qrFilePath, QR_ECLEVEL_H, 4);

    // Update the QR code path in the database only if $cardID is not null
    if ($cardID !== null) {
        $updateQR = "UPDATE MembershipCard SET QRCode = '$qrFilePath' WHERE CardID = $cardID";
        mysqli_query($conn, $updateQR);
    }

    return $qrFilePath;
}

// Handle card creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_card'])) {
    $balance = 0.00;
    $qrFilePath = regenerateQRCode($studID, $balance, $qrDirectory, null, $conn);

    $stmt = "INSERT INTO MembershipCard (studID, QRCode, Balance) VALUES ($studID, '$qrFilePath', $balance)";
    if (mysqli_query($conn, $stmt)) {
        header("Location: membership-card.php");
        exit();
    } else {
        $message = "Error creating membership card.";
    }
}

// Handle card cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_card'])) {
    $cardID = $_POST['cardID'];
    $stmt = "DELETE FROM MembershipCard WHERE CardID = $cardID";
    if (mysqli_query($conn, $stmt)) {
        // Remove the QR code file
        if (file_exists($card['QRCode'])) {
            unlink($card['QRCode']);
        }
        header("Location: membership-card.php");
        exit();
    } else {
        $message = "Error canceling membership card.";
    }
}

// Handle adding funds
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds'])) {
    $cardID = $_POST['cardID'];
    $amount = $_POST['amount'];
    $currDate = date('Y-m-d H:i:s');
    if ($amount > 0) {
        // Update the balance
        $stmt = "UPDATE MembershipCard SET Balance = Balance + $amount WHERE CardID = $cardID";
        if (mysqli_query($conn, $stmt)) {
            // Insert a record into the Transactions table
            $transactionStmt = "INSERT INTO transactions (CardID, Type, Amount, Date) VALUES ($cardID, 'Add Funds', $amount, '$currDate')";
            mysqli_query($conn, $transactionStmt);

            // Fetch updated balance to regenerate QR code
            $updatedCardQuery = "SELECT Balance FROM MembershipCard WHERE CardID = $cardID";
            $updatedCardResult = mysqli_query($conn, $updatedCardQuery);
            $updatedCard = mysqli_fetch_assoc($updatedCardResult);
            $newBalance = $updatedCard['Balance'];

            // Regenerate QR code with the new balance
            $qrFilePath = regenerateQRCode($studID, $newBalance, $qrDirectory, $cardID, $conn);

            // Redirect to refresh the page
            header("Location: membership-card.php?success=1");
            exit();
        } else {
            $message = "Error adding funds.";
        }
    } else {
        $message = "Invalid amount.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Card</title>
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
                    <li><a href="Dashboard.php">Dashboard</a></li>
                    <li><a href="membership-card.php">Membership Card</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main bg-light py-5">
        <div class="container">
            <h1 class="text-center mb-4">Membership Card Details</h1>

            <?php if (!empty($message)) : ?>
                <div class="alert alert-danger"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($card) : ?>
                <div class="card shadow-sm p-4 mx-auto" style="max-width: 500px;">
                    <h4 class="card-title">Membership Card Details</h4>
                    <p><strong>Card ID:</strong> <?= htmlspecialchars($card['CardID']); ?></p>
                    <p><strong>Balance:</strong> RM<?= number_format($card['Balance'], 2); ?></p>
                    <p><strong>QR Code:</strong></p>
                    <img src="<?= htmlspecialchars($card['QRCode']) . '?t=' . time(); ?>" alt="QR Code" class="img-fluid">

                    <!-- add funds -->
                    <form method="POST" action="" class="mt-3">
                        <input type="hidden" name="cardID" value="<?= $card['CardID']; ?>">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Add Funds</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" required>
                        </div>
                        <button type="submit" name="add_funds" class="btn btn-success w-100">Add Funds</button>
                    </form>

                    <!-- cancel -->
                    <form method="POST" action="" class="mt-3">
                        <input type="hidden" name="cardID" value="<?= $card['CardID']; ?>">
                        <button type="submit" name="cancel_card" class="btn btn-danger w-100">Cancel Membership Card</button>
                    </form>
                </div>
            <?php else : ?>
                <div class="card shadow-sm p-4 mx-auto text-center" style="max-width: 500px;">
                    <h4>No membership details found.</h4>
                    <form method="POST" action="" class="mt-3">
                        <button type="submit" name="create_card" class="btn btn-primary w-100">Create Membership Card</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
