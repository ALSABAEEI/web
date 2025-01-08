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

//  QR directory
if (!file_exists($qrDirectory)) {
    mkdir($qrDirectory, 0777, true);
}

// get membership card details
if ($studID) {
    $query = "SELECT * FROM MembershipCard WHERE studID = $studID";
    $result = mysqli_query($conn, $query);
    $card = mysqli_fetch_assoc($result);
}

// generate new QR code function
function regenerateQRCode($studID, $qrDirectory, $conn)
{
    // get new balance
    $query = "SELECT Balance FROM MembershipCard WHERE studID = $studID";
    $result = mysqli_query($conn, $query); // Pass the $conn (mysqli object) correctly here
    $card = mysqli_fetch_assoc($result);

    if ($card) {
        $balance = $card['Balance'];
        $qrValue = "Student ID: $studID, Balance: RM" . number_format($balance, 2);
        $qrFilePath = $qrDirectory . "card_" . $studID . ".png";

        // generate the QR code image 
        QRcode::png($qrValue, $qrFilePath, QR_ECLEVEL_H, 4);

        return $qrFilePath;
    }

    return null;
}


// with each refresh
regenerateQRCode($studID, $qrDirectory, $conn);


//  card creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_card'])) {
    $balance = 0.00;

    // QR code
    regenerateQRCode($studID, $qrDirectory, $conn);


    $stmt = "INSERT INTO MembershipCard (studID, QRCode, Balance) VALUES ($studID, 'qrcodes/card_" . $studID . ".png', $balance)";
    if (mysqli_query($conn, $stmt)) {
        header("Location: membership-card.php");
        exit();
    } else {
        $message = "Error creating membership card.";
    }
}



//  card cancellation
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
        // to update the balance
        $stmt = "UPDATE MembershipCard SET Balance = Balance + $amount WHERE CardID = $cardID";
        if (mysqli_query($conn, $stmt)) {

            $transactionStmt = "INSERT INTO transactions (CardID, Type, Amount, Date) VALUES ($cardID, 'Add Funds', $amount, '$currDate')";
            mysqli_query($conn, $transactionStmt);

            // new QR code with new balance
            regenerateQRCode($studID, $qrDirectory, $conn);

            header("Location: membership-card.php?success=1");
            exit();
        } else {
            $message = "Error adding funds.";
        }
    } else {
        $message = "Invalid amount.";
    }
}

// search
if  (isset($_POST['search'])) {
    $searchID = $_POST['searchCardID'];
    $sQuery = "SELECT Balance FROM MembershipCard WHERE CardID = $searchID";
    $sResult = mysqli_query($conn, $sQuery);
    $searchCard = mysqli_fetch_assoc($sResult);

    if ($searchCard) {
        $message = "Balance for Card $searchID: RM" . $searchCard['Balance'];
    } else {
        $message = "Card ID $searchCardID not found.";
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

            <?php if (!empty($message)) : ?>
                <div class="alert alert-danger"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($card) : ?>
                <div class="card shadow-sm p-4 mx-auto" style="max-width: 500px;">
                    <h4 class="card-title">Membership Card Details</h4>
                    <p><strong>Card ID:</strong> <?= htmlspecialchars($card['CardID']); ?></p>
                    <p><strong>QR Code:</strong></p>
                    <img src="<?= htmlspecialchars($card['QRCode']) . '?t=' . time(); ?>" alt="QR Code" class="img-fluid">

                    <div class="card shadow-sm p-4 mx-auto mb-4" style="max-width: 500px;">

                    <!--  (search) -->
    <h4 class="card-title">Search Membership Card</h4>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="searchCardID" class="form-label">Enter Card ID</label>
            <input type="number" class="form-control" id="searchCardID" name="searchCardID" placeholder="Card ID" required>
        </div>
        <button type="submit" name="search" class="btn btn-primary w-100">Search</button>
    </form>
</div>


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