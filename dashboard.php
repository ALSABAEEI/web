<?php
session_start();

if (!isset($_SESSION['studID']) || empty($_SESSION['studID'])) {
    header('Location: login.php');
    exit();
}

$studID = $_SESSION['studID'];

$conn = new mysqli("localhost", "root", "", "rapidprint");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// get student 
$studentQuery = "SELECT * FROM Student WHERE studID = $studID";
$studentResult = mysqli_query($conn, $studentQuery);
$student = mysqli_fetch_assoc($studentResult);

// get membership card 
$cardQuery = "SELECT * FROM MembershipCard WHERE studID = $studID";
$cardResult = mysqli_query($conn, $cardQuery);
$membershipCard = mysqli_fetch_assoc($cardResult);

// get transactions 
$transactions = [];
if ($membershipCard) {
    $transactionQuery = $transactionQuery = "
        SELECT transc.*, mem.Balance 
        FROM Transactions transc
        JOIN MembershipCard mem ON transc.CardID = mem.CardID
        WHERE transc.CardID = {$membershipCard['CardID']}
        ORDER BY transc.Date DESC;
        ";

    $transactionResult = mysqli_query($conn, $transactionQuery);
    while ($row = mysqli_fetch_assoc($transactionResult)) {
        $transactions[] = $row;
    }
}

// calc orders
$orderQuery = "SELECT COUNT(*) AS OrderCount FROM Orders WHERE studID = $studID";
$orderResult = mysqli_query($conn, $orderQuery);
$orderCount = mysqli_fetch_assoc($orderResult)['OrderCount'];

// get monthly spending
$spendingQuery = "
    SELECT MONTH(Date) AS Month, SUM(OrderTotal) AS TotalSpent 
    FROM Orders 
    WHERE studID = $studID AND YEAR(Date) = YEAR(CURRENT_DATE)
    GROUP BY MONTH(Date)";
$spendingResult = mysqli_query($conn, $spendingQuery);

$yearlySpending = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($spendingResult)) {
    $yearlySpending[intval($row['Month'])] = $row['TotalSpent'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Student Dashboard - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="starter-page-page">

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
            <div class="row">
                <div class="col-lg-12 text-center mb-4">
                    <h1>Student Dashboard</h1>
                    <p>Welcome, <?= htmlspecialchars($student['UserName']); ?>!</p>
                </div>
            </div>

            <div class="row gy-4">
                <!-- Profile Summary -->
                <div class="col-lg-4">
                    <div class="card shadow-sm p-3">
                        <h4>Profile Summary</h4>
                        <p><strong>Name:</strong> <?= htmlspecialchars($student['UserName']); ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($student['email']); ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($student['PhoneNumber']); ?></p>
                        <a href="update-student-info.php" class="btn btn-primary mt-2">Edit Profile</a>
                    </div>
                </div>

                <!-- Membership Card Details -->
                <div class="col-lg-4">
                    <div class="card shadow-sm p-3">
                        <h4>Membership Card</h4>
                        <?php if ($membershipCard): ?>
                            <p><strong>Card ID:</strong> <?= htmlspecialchars($membershipCard['CardID']); ?></p>
                            <p><strong>Balance:</strong> RM<?= htmlspecialchars($membershipCard['Balance']); ?></p>
                            <a href="membership-card.php" class="btn btn-primary mt-2">View Details</a>
                        <?php else: ?>
                            <p>No Membership Card Found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="col-lg-4">
                    <div class="card shadow-sm p-3">
                        <h4>Orders</h4>
                        <p><strong>Total Orders:</strong> <?= $orderCount; ?></p>
                        <a href="orders.php" class="btn btn-primary mt-2">View All Orders</a>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <!-- Transaction Table -->
                <div class="col-lg-6">
                    <div class="card shadow-sm p-3">
                        <h4>Recent MembershipCard Activities</h4>
                        <?php if ($transactions): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($transaction['Date']); ?></td>
                                            <td>RM<?= htmlspecialchars($transaction['Amount']); ?></td>
                                            <td><?= htmlspecialchars($transaction['Type']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No transactions found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Monthly Spending Chart -->
                <div class="col-lg-6">
                    <div class="card shadow-sm p-3">
                        <h4>Monthly Spending</h4>
                        <canvas id="spendingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('spendingChart').getContext('2d');
        const labels = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const data = <?= json_encode(array_values($yearlySpending)); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Spending (RM)',
                    data: data,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Months'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Spending (RM)'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>