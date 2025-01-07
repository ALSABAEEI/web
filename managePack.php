<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Inline database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rapidprint";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle package deletion
if (isset($_GET['deletePackage'])) {
  $packageID = intval($_GET['deletePackage']); // Ensure $packageID is a valid integer
  $deleteQuery = "DELETE FROM package WHERE PackageID = $packageID"; // Directly insert the value
  if (mysqli_query($conn, $deleteQuery)) {
      echo "<div class='alert alert-success'>Package deleted successfully!</div>";
  } else {
      echo "<div class='alert alert-danger'>Error deleting package: " . mysqli_error($conn) . "</div>";
  }
}

// Get BranchID from the query parameter
$branchID = isset($_GET['BranchID']) ? intval($_GET['BranchID']) : 0;

if ($branchID === 0) {
  die("Invalid branch selected.");
}

// Fetch packages for the selected branch
$query = "SELECT PackageID, PackageName, Description, Price FROM package WHERE BranchID = $branchID";
$result = mysqli_query($conn, $query); // Execute the query

if (!$result) {
  die("Database query failed: " . mysqli_error($conn));
}

// Fetch branch name for display
$branchQuery = "SELECT BranchName FROM branch WHERE BranchID = $branchID";
$branchResult = mysqli_query($conn, $branchQuery); // Execute the query
$branch = mysqli_fetch_assoc($branchResult);
$branchName = $branch ? htmlspecialchars($branch['BranchName']) : "Unknown";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Packages - <?php echo $branchName; ?></title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
  <div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
      <a href="manageBran.php" class="navbar-brand d-flex align-items-center">
        <strong>RP</strong>
      </a>
    </div>
  </div>

<main>
  <section class="py-5 text-center container">
    <div class="row py-lg-5">
      <div class="col-lg-6 col-md-8 mx-auto">
        <h1 class="fw-light">Manage Packages - <?php echo $branchName; ?></h1>
        <p>
          <a href="manageBran.php" class="btn btn-primary">Back to Branches</a>
        </p>
      </div>
    </div>
  </section>

  <div class="album py-5 bg-body-tertiary">
    <div class="container">
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
          <div class="col">
            <div class="card shadow-sm">
              <div class="card-body">
                <h2><?php echo htmlspecialchars($row['PackageName']); ?></h2>
                <h5>Price:</h5> RM<?php echo htmlspecialchars($row['Price']); ?>
                <h5>Description:</h5> <?php echo htmlspecialchars($row['Description']); ?>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="btn-group">
                  <a href="updatePack.php?PackageID=<?php echo $row['PackageID']; ?>&BranchID=<?php echo $branchID; ?>" class="btn btn-sm btn-outline-secondary">Update</a>
                  <a href="managePack.php?BranchID=<?php echo $branchID; ?>&deletePackage=<?php echo $row['PackageID']; ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Are you sure you want to delete this package?');">Delete</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <div class="text-center mt-4">
  <a href="addPackage.php?BranchID=<?php echo $branchID; ?>" class="btn btn-success btn-add-package">Add New Package</a>
  </div>
</main>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
