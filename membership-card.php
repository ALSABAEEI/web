<?php
session_start();

if (!isset($_SESSION['studID']) || empty($_SESSION['studID'])) {
    header('Location: login.php');
    exit();
}

include('phpqrcode/qrlib.php');

$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$studID = $_SESSION['studID'] ?? null;
$message = "";
$card = null;
$qrDirectory = "qrcodes/";

if (!file_exists($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

// get membership card 
$query = "SELECT * FROM MembershipCard WHERE studID = $studID";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $card = mysqli_fetch_assoc($result);
} else {
    $card = null; 
}

// regenerate qr code
function regenerateQRCode($cardID, $balance, $qrDirectory)
{
    $qrValue = "http://localhost/Web/card-details.php?CardID=" . urlencode($cardID);
    $qrFilePath = $qrDirectory . "card_" . $cardID . ".png";

    // QR code image
    QRcode::png($qrValue, $qrFilePath, QR_ECLEVEL_H, 4);

    return $qrFilePath;
}

if ($card) {
    $qrFilePath = regenerateQRCode($card['CardID'], $card['Balance'], $qrDirectory);
    $updateQuery = "UPDATE MembershipCard SET QRCode = '$qrFilePath' WHERE CardID = {$card['CardID']}";
    mysqli_query($conn, $updateQuery);
}

//  card create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_card'])) {
    $balance = 0.00;

    $stmt = "INSERT INTO MembershipCard (studID, QRCode, Balance) VALUES ($studID, '', $balance)";
    if (mysqli_query($conn, $stmt)) {
        //  the new created card
        $newCardID = mysqli_insert_id($conn);

        
        $qrFilePath = regenerateQRCode($newCardID, $balance, $qrDirectory);

        
        $updateQuery = "UPDATE MembershipCard SET QRCode = '$qrFilePath' WHERE CardID = $newCardID";
        mysqli_query($conn, $updateQuery);

        header("Location: membership-card.php");
        exit();
    } else {
        $message = "Error creating membership card.";
    }
}

//  card cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_card'])) {
    $cardID = $_POST['cardID'];
    $stmt = "DELETE FROM MembershipCard WHERE CardID = $cardID";
    if (mysqli_query($conn, $stmt)) {
        if (file_exists($card['QRCode'])) {
            unlink($card['QRCode']);
        }
        header("Location: membership-card.php");
        exit();
    } else {
        $message = "Error canceling membership card.";
    }
}

//  add funds
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_funds'])) {
    $cardID = $_POST['cardID'];
    $amount = $_POST['amount'];
    $currDate = date('Y-m-d H:i:s');
    if ($amount > 0) {
        $stmt = "UPDATE MembershipCard SET Balance = Balance + $amount WHERE CardID = $cardID";
        if (mysqli_query($conn, $stmt)) {
            $transactionStmt = "INSERT INTO transactions (CardID, Type, Amount, Date) VALUES ($cardID, 'Add Funds', $amount, '$currDate')";
            mysqli_query($conn, $transactionStmt);

            $query = "SELECT * FROM MembershipCard WHERE CardID = $cardID";
            $result = mysqli_query($conn, $query);
            $card = mysqli_fetch_assoc($result);

            regenerateQRCode($cardID, $card['Balance'], $qrDirectory);

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


    <script>
    function confirmCancellation(event) {
        if (!confirm("Are you sure you want to cancel this membership card?")) {
            event.preventDefault();
        }
    }
</script>

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
            <h1 class="text-center mb-4">Membership Card Details</h1>



            <?php if ($card) : ?>
                <div class="card shadow-sm p-4 mx-auto" style="max-width: 500px;">
                    <h4 class="card-title">Membership Card Details</h4>
                    <p><strong>Card ID:</strong> <?= htmlspecialchars($card['CardID']); ?></p>
                    <p><strong>QR Code:</strong></p>
                    <img src="<?= htmlspecialchars($card['QRCode']) . '?t=' . time(); ?>" alt="QR Code" class="img-fluid">

                    <div class="card shadow-sm p-4 mx-auto mb-4" style="max-width: 500px;">




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
                        <button type="submit" name="cancel_card" class="btn btn-danger w-100" onclick="confirmCancellation(event)">Cancel Membership Card</button>
                    </form>
                </div>
            <?php else : ?>
                <div class="card shadow-sm p-4 mx-auto text-center" style="max-width: 500px;">
                    <h4>No membership details found.</h4>
                    <form method="POST" action="" class="mt-3">
                        <button type="submit" name="create_card" class="btn btn-primary w-100" >Create Membership Card</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>