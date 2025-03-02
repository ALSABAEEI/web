<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

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

// Amro Fetch data for the header section
$totalBranchesQuery = "SELECT COUNT(BranchID) AS totalBranches FROM branch";
$totalBranchesResult = mysqli_query($conn, $totalBranchesQuery);
$totalBranches = mysqli_fetch_assoc($totalBranchesResult)['totalBranches'];

$totalPackagesQuery = "SELECT COUNT(PackageID) AS totalPackages FROM package";
$totalPackagesResult = mysqli_query($conn, $totalPackagesQuery);
$totalPackages = mysqli_fetch_assoc($totalPackagesResult)['totalPackages'];

$totalRevenueQuery = "SELECT SUM(OrderTotal) AS totalRevenue FROM orders";
$totalRevenueResult = mysqli_query($conn, $totalRevenueQuery);
$totalRevenue = mysqli_fetch_assoc($totalRevenueResult)['totalRevenue'];

// Amro Fetch data for the bar chart (Orders per Branch)
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

// Amro Fetch data for the pie chart (Revenue per Branch)
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

// Hisham Fetch data for Monthly Orders Trend (Line Chart)
$monthlyOrdersQuery = "
  SELECT DATE_FORMAT(o.Date, '%Y-%m') AS Month, b.BranchName, COUNT(o.OrderID) AS TotalOrders
  FROM orders o
  JOIN branch b ON o.BranchID = b.BranchID
  GROUP BY Month, b.BranchName
  ORDER BY Month, b.BranchName";
$monthlyOrdersResult = mysqli_query($conn, $monthlyOrdersQuery);

$months = [];
$branchesForMonth = [];
$monthlyOrders = [];
while ($row = mysqli_fetch_assoc($monthlyOrdersResult)) {
  $months[] = $row['Month'];
  $branchesForMonth[] = $row['BranchName'];
  $monthlyOrders[] = $row['TotalOrders'];
}


// Hisham Fetch data for Order Status Distribution (Doughnut Chart)
$orderStatusQuery = "
    SELECT b.BranchName, o.OrderStatus, COUNT(o.OrderID) AS TotalOrders
    FROM orders o
    JOIN branch b ON o.BranchID = b.BranchID
    GROUP BY b.BranchName, o.OrderStatus";
$orderStatusResult = mysqli_query($conn, $orderStatusQuery);

$branchesForStatus = [];
$orderStatuses = [];
$orderStatusCounts = [];
while ($row = mysqli_fetch_assoc($orderStatusResult)) {
  $branchesForStatus[] = $row['BranchName'];
  $orderStatuses[] = $row['OrderStatus'];
  $orderStatusCounts[] = $row['TotalOrders'];
}


// Amro Handle Order Status Search
$orderSearchResults = [];
if (!empty($_GET['order_status'])) {
  $orderStatus = trim($_GET['order_status']);
  $orderStatusQuery = "
    SELECT orders.*, branch.BranchName 
    FROM orders 
    LEFT JOIN branch ON orders.BranchID = branch.BranchID 
    WHERE orders.OrderStatus = '$orderStatus'";
  $orderSearchResults = mysqli_query($conn, $orderStatusQuery);
}

// Amro Handle Date-based Search
$dateSearchResults = [];
if (!empty($_GET['search_date'])) {
  $searchDate = trim($_GET['search_date']);
  $dateQuery = "
    SELECT orders.*, branch.BranchName 
    FROM orders 
    LEFT JOIN branch ON orders.BranchID = branch.BranchID 
    WHERE orders.Date = '$searchDate'";
  $dateSearchResults = mysqli_query($conn, $dateQuery);
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

    .header .btn-manage-branch,
    .header .btn-logout {
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
        <a href="manage-students.php" class="btn btn-sm btn-manage-branch">Manage students</a>
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
              <p class="card-text"><?php echo number_format($totalRevenue, 2); ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <br>
    <hr>
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
      <br><br>
      <hr>
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
              data: revenues, //revenue for each peice in the pie
              backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)']
            }]
          }
        });
      </script>
      <br><br>
      <hr>
      <h4>Monthly Orders Trend</h4>
      <canvas id="monthlyOrdersChart"></canvas>
      <script>
        const months = <?php echo json_encode($months); ?>;
        const branchesForMonth = <?php echo json_encode($branchesForMonth); ?>;
        const monthlyOrders = <?php echo json_encode($monthlyOrders); ?>;

        // Group data by branches
        const dataByBranch = {};
        months.forEach((month, index) => {
          const branch = branchesForMonth[index];
          const orders = monthlyOrders[index];

          if (!dataByBranch[branch]) {
            dataByBranch[branch] = {
              labels: [],
              data: []
            };
          }

          dataByBranch[branch].labels.push(month);
          dataByBranch[branch].data.push(orders);
        });

        // Prepare datasets
        const datasets = Object.keys(dataByBranch).map(branch => ({
          label: branch,
          data: dataByBranch[branch].data,
          borderColor: `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 1)`,
          fill: false
        }));

        // Render the chart
        const ctx3 = document.getElementById('monthlyOrdersChart').getContext('2d');
        new Chart(ctx3, {
          type: 'line',
          data: {
            labels: [...new Set(months)], // Unique months
            datasets: datasets
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: true,
                position: 'top'
              }
            }
          }
        });
      </script>
      <br><br>
      <hr>
      <h4>Order Status Distribution</h4>
      <canvas id="orderStatusChart"></canvas>
      <script>
        const branchesForStatus = <?php echo json_encode($branchesForStatus); ?>;
        const orderStatuses = <?php echo json_encode($orderStatuses); ?>;
        const orderStatusCounts = <?php echo json_encode($orderStatusCounts); ?>;

        // Combine branch and status for labels
        const labels = branchesForStatus.map((branch, index) => `${branch} - ${orderStatuses[index]}`);


        const backgroundColors = labels.map(() => {
          const r = Math.floor(Math.random() * 255);
          const g = Math.floor(Math.random() * 255);
          const b = Math.floor(Math.random() * 255);
          return `rgba(${r}, ${g}, ${b}, 0.6)`;
        });

        const ctx4 = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(ctx4, {
          type: 'doughnut',
          data: {
            labels: labels,
            datasets: [{

              data: orderStatusCounts,

              backgroundColor: backgroundColors
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                display: true,
                position: 'right'
              },
              tooltip: {
                callbacks: {
                  label: (context) => {
                    const label = context.label;
                    const count = context.raw;
                    return `${label}: ${count}`;
                  }
                }
              }
            }
          }
        });
      </script>

    </div>
    <br><br>
    <hr><br>
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
            <?php while ($row = mysqli_fetch_assoc($orderSearchResults)) : ?>
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