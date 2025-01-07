<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Logout Logicjhjh
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Get the admin's name from the session
$adminName = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "Admin";

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rapidprint";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch data for the header section
$totalBranchesQuery = "SELECT COUNT(BranchID) AS totalBranches FROM branch";
$totalBranchesResult = mysqli_query($conn, $totalBranchesQuery); // to run the query
$totalBranches = mysqli_fetch_assoc($totalBranchesResult)['totalBranches'];

$totalPackagesQuery = "SELECT COUNT(PackageID) AS totalPackages FROM package";
$totalPackagesResult = mysqli_query($conn, $totalPackagesQuery);
$totalPackages = mysqli_fetch_assoc($totalPackagesResult)['totalPackages'];

$totalRevenueQuery = "SELECT SUM(OrderTotal) AS totalRevenue FROM orders";
$totalRevenueResult = mysqli_query($conn, $totalRevenueQuery);
$totalRevenue = mysqli_fetch_assoc($totalRevenueResult)['totalRevenue'];

// Fetch data for the bar chart (Orders per Branch)
$ordersPerBranchQuery = "SELECT b.BranchName, COUNT(o.OrderID) AS totalOrders 
                         FROM orders o 
                         JOIN branch b ON o.BranchID = b.BranchID 
                         GROUP BY o.BranchID";
$ordersPerBranchResult = mysqli_query($conn, $ordersPerBranchQuery);
$branches = [];
$orders = [];
while ($row = mysqli_fetch_assoc($ordersPerBranchResult)) {
    $branches[] = $row['BranchName'];
    $orders[] = $row['totalOrders'];
}

// Fetch data for the pie chart (Revenue per Branch)
$revenuePerBranchQuery = "
    SELECT b.BranchName, SUM(o.OrderTotal) AS revenue
    FROM orders o
    JOIN branch b ON o.BranchID = b.BranchID
    GROUP BY o.BranchID";
$revenuePerBranchResult = mysqli_query($conn, $revenuePerBranchQuery);
$branchesForRevenue = [];
$revenues = [];
while ($row = mysqli_fetch_assoc($revenuePerBranchResult)) {
    $branchesForRevenue[] = $row['BranchName'];
    $revenues[] = $row['revenue'];
}

// Handle Order Status Search
$orderSearchResults = [];
if (!empty($_GET['order_status'])) {
    $orderStatus = trim($_GET['order_status']); // Clean the input
    $orderStatusQuery = "
    SELECT orders.*, branch.BranchName 
    FROM orders 
    LEFT JOIN branch ON orders.BranchID = branch.BranchID 
    WHERE orders.OrderStatus = '$orderStatus'"; // Directly insert the value into the query
    $orderSearchResults = mysqli_query($conn, $orderStatusQuery); // Execute the query
}

// Handle Date-based Search
$dateSearchResults = [];
if (!empty($_GET['search_date'])) {
    $searchDate = trim($_GET['search_date']); // Clean the input
    $dateQuery = "
    SELECT orders.*, branch.BranchName 
    FROM orders 
    LEFT JOIN branch ON orders.BranchID = branch.BranchID 
    WHERE orders.Date = '$searchDate'"; // Directly insert the value into the query
    $dateSearchResults = mysqli_query($conn, $dateQuery); // Execute the query
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    canvas {
      max-height: 300px;
    }

    .header {
      background-color: #343a40;
      color: #ffffff;
      padding: 10px 20px;
    }

    .header .btn-manage-branch, .header .btn-logout {
      margin-left: 10px;
      border: 1px solid #ffc107;
      color: #ffc107;
    }

    .search-section {
      margin-top: 30px;
    }
  </style>
</head>

<body class="admin-page">
  <header class="header d-flex align-items-center justify-content-between">
    <div class="container-fluid d-flex align-items-center justify-content-between">
      <a href="dashboard.php" class="logo">RP<span>.</span></a>
      <div class="d-flex align-items-center">
        <span class="text-light me-3">Welcome, <?php echo htmlspecialchars($adminName); ?>!</span>
        <a href="manageBran.php" class="btn btn-sm btn-manage-branch">Manage Branch</a>
        <a href="adminDash.php?logout=true" class="btn btn-sm btn-logout">Log Out</a>
      </div>
    </div>
  </header>

  <main class="main" style="margin-top: 100px;">
    <!-- Header Section -->
    <div class="container text-center">
      <div class="row">
        <div class="col-md-4">
          <div class="card text-white bg-primary mb-3">
            <div class="card-body">
              <h5 class="card-title">Total Branches</h5>
              <p class="card-text"><?php echo $totalBranches; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white bg-success mb-3">
            <div class="card-body">
              <h5 class="card-title">Total Packages</h5>
              <p class="card-text"><?php echo $totalPackages; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-white bg-info mb-3">
            <div class="card-body">
              <h5 class="card-title">Total Revenue (RM)</h5>
              <p class="card-text"><?php echo number_format($totalRevenue, 2); ?></p> <!-- commas, decimal -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <br><hr>
    <!-- Graph Section -->
    <div class="container">
      <h4>Orders per Branch</h4>
      <canvas id="ordersChart"></canvas>
      <script>
        const branches = <?php echo json_encode($branches); ?>;
        const orders = <?php echo json_encode($orders); ?>;
        const ctx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: {
            labels: branches, // x
            datasets: [{
              label: 'Orders',
              data: orders, // y
              backgroundColor: 'rgba(75, 192, 192, 0.6)',
            }]
          }
        });
      </script>
<br><br><hr>
      <h4>Revenue Distribution</h4>
      <canvas id="revenueChart"></canvas>
      <script>
        const branchesForRevenue = <?php echo json_encode($branchesForRevenue); ?>;
        const revenues = <?php echo json_encode($revenues); ?>;
        const ctx2 = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx2, {
          type: 'pie',
          data: {
            labels: branchesForRevenue, //branches names
            datasets: [{
              data: revenues, //names for each peice in the pie chart
              backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)']
            }]
          }
        });
      </script>
    </div>
<br><br><hr><br>
    <!-- Search Section -->
    <div class="container search-section">
      <h4>Search Orders</h4>
      <div class="row">
        <div class="col-md-6">
          <form method="GET" action="">
            <div class="input-group">
              <select name="order_status" class="form-select">
                <option value="" selected disabled>Select Order Status</option>
                <option value="Unpaid">Unpaid</option>
                <option value="Ordered">Ordered</option>
              </select>
              <button type="submit" class="btn btn-primary">Search by Status</button>
            </div>
          </form>
        </div>
        <div class="col-md-6">
          <form method="GET" action="">
            <div class="input-group">
              <input type="date" name="search_date" class="form-control" required>
              <button type="submit" class="btn btn-primary">Search by Date</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<br><br><br>
    <!-- Search Results -->
    <div class="container">
      <?php if (!empty($orderSearchResults)) : ?>
        <h4 class="mt-4">Search Results (Order Status)</h4>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Order Status</th>
              <th>Date</th>
              <th>BranchName</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($orderSearchResults)) : // row here differ from previous defined ?> 
              <tr>
                <td><?php echo htmlspecialchars($row['OrderID']); ?></td>
                <td><?php echo htmlspecialchars($row['OrderStatus']); ?></td>
                <td><?php echo htmlspecialchars($row['Date']); ?></td>
                <td><?php echo htmlspecialchars($row['BranchName']); ?></td>
                <td><?php echo htmlspecialchars($row['OrderTotal']); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if (!empty($dateSearchResults)) : ?>
        <h4 class="mt-4">Search Results (Date)</h4>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Order Status</th>
              <th>Date</th>
              <th>BranchName</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = mysqli_fetch_assoc($dateSearchResults)) : ?>
              <tr>
                <td><?php echo htmlspecialchars($row['OrderID']); ?></td>
                <td><?php echo htmlspecialchars($row['OrderStatus']); ?></td>
                <td><?php echo htmlspecialchars($row['Date']); ?></td>
                <td><?php echo htmlspecialchars($row['BranchName']); ?></td>
                <td><?php echo htmlspecialchars($row['OrderTotal']); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
