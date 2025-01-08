<?php
$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['CardID'])) {
    $cardID = intval($_GET['CardID']);


    $cardQuery = "SELECT * FROM MembershipCard WHERE CardID = $cardID";
    $cardResult = mysqli_query($conn, $cardQuery);
    $card = mysqli_fetch_assoc($cardResult);

    $transactions = [];
    $transactionQuery = "SELECT * FROM Transactions WHERE CardID = $cardID ORDER BY Date DESC";
    $transactionResult = mysqli_query($conn, $transactionQuery);
    while ($row = mysqli_fetch_assoc($transactionResult)) {
        $transactions[] = $row;
    }
} else {
    die("CardID not provided!");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Details</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Membership Card Details</h1>

        <?php if ($card): ?>
            <div class="card p-3">
                <h3>Card Information</h3>
                <p><strong>Card ID:</strong> <?= htmlspecialchars($card['CardID']); ?></p>
                <p><strong>Balance:</strong> RM<?= htmlspecialchars(number_format($card['Balance'], 2)); ?></p>
            </div>

            <div class="card p-3 mt-4">
                <h3>Transaction History</h3>
                <?php if ($transactions): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['Date']); ?></td>
                                    <td><?= htmlspecialchars($transaction['Type']); ?></td>
                                    <td>RM<?= htmlspecialchars($transaction['Amount']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No transactions found.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Card not found!</p>
        <?php endif; ?>
    </div>
</body>
</html>
