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

// Handle branch deletion
if (isset($_GET['deleteBranch'])) {
  $branchID = intval($_GET['deleteBranch']); 
  $deleteQuery = "DELETE FROM branch WHERE BranchID = $branchID"; 
  if (mysqli_query($conn, $deleteQuery)) {
      $message = "Branch deleted successfully.";
  } else {
      $message = "Error deleting branch: " . mysqli_error($conn);
  }
}

// Fetch branches from the database
$query = "SELECT BranchID, BranchName, Location, ContactInfo FROM branch";
$result = mysqli_query($conn, $query);

if (!$result) {
  die("Database query failed: " . mysqli_error($conn));
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Branches</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    .btn-add-branch {
      background-color: #28a745; 
      color: #fff; 
      padding: 10px 20px; 
      font-size: 16px; 
      font-weight: bold; 
      border-radius: 5px; 
      border: none; 
      transition: background-color 0.3s ease, transform 0.3s ease; 
    }

    .btn-add-branch:hover {
      background-color: #218838; 
      transform: scale(1.05); 
      cursor: pointer; 
    }

    .btn-add-branch:active {
      background-color: #1e7e34; 
      transform: scale(0.95); 
    }
  </style>
</head>
<body>
  <div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
      <a href="adminDash.php" class="navbar-brand d-flex align-items-center">
        <strong>RP</strong>
      </a>
    </div>
  </div>

<main>
  <section class="py-5 text-center container">
    <div class="row py-lg-5">
      <div class="col-lg-6 col-md-8 mx-auto">
        <h1 class="fw-light">Manage Branches</h1>
        <?php if (isset($message)): ?>
          <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($message); ?>
          </div>
        <?php endif; ?>
        <p>
          <a href="adminDash.php" class="btn btn-primary">Go to Dashboard</a>
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
                <h2><?php echo htmlspecialchars($row['BranchName']); ?></h2>
                <h5>Location:</h5> <?php echo htmlspecialchars($row['Location']); ?>
                <h5>Contact:</h5> <?php echo htmlspecialchars($row['ContactInfo']); ?>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="btn-group">
                  <a href="updateBranch.php?BranchID=<?php echo $row['BranchID']; ?>" class="btn btn-sm btn-outline-secondary">Update</a>
                  <a href="manageBran.php?deleteBranch=<?php echo $row['BranchID']; ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Are you sure you want to delete this branch?');">Delete</a>
                  <a href="managePack.php?BranchID=<?php echo $row['BranchID']; ?>" class="btn btn-sm btn-outline-secondary">View Packages</a>
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
    <a href="addBranch.php" class="btn btn-add-branch">Add New Branch</a>
</div>

</main>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
