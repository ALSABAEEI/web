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

// Get PackageID and BranchID
$packageID = isset($_GET['PackageID']) ? intval($_GET['PackageID']) : 0;
$branchID = isset($_GET['BranchID']) ? intval($_GET['BranchID']) : 0;

if ($packageID === 0 || $branchID === 0) {
    die("Invalid package or branch.");
}

// Fetch package details for pre-filling the form
$query = "SELECT PackageName, Description, Price FROM package WHERE PackageID = $packageID"; 
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $package = mysqli_fetch_assoc($result);
} else {
    die("Package not found.");
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageName = trim($_POST['packageName']); 
    $description = trim($_POST['description']); 
    $price = floatval($_POST['price']); 

    // Validate inputs
    if (empty($packageName) || empty($description) || $price <= 0) {
        $message = "All fields are required, and price must be greater than 0.";
    } else {
        // Update query
        $updateQuery = "UPDATE package 
                        SET PackageName = '$packageName', Description = '$description', Price = $price 
                        WHERE PackageID = $packageID";

        if (mysqli_query($conn, $updateQuery)) {
            $message = "Package updated successfully!";
        } else {
            $message = "Error updating package: " . mysqli_error($conn);
        }
    }
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Update Package</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<style>

.mt-3{

  margin-left: 269px;

}

</style>

<body>
<a href="manageBran.php" class="btn btn-secondary mt-3">Back</a>
  <div class="container mt-5">
    <h1 class="mb-4">Update Package</h1>

    <?php if (!empty($message)): ?>
      <div class="alert alert-info" role="alert">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="packageName" class="form-label">Package Name</label>
        <input type="text" class="form-control" id="packageName" name="packageName" value="<?php echo htmlspecialchars($package['PackageName']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($package['Description']); ?></textarea>
      </div>
      <div class="mb-3">
        <label for="price" class="form-label">Price</label>
        <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($package['Price']); ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Update Package</button>
      <a href="managePack.php?BranchID=<?php echo $branchID; ?>" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</body>
</html>
