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

// Fetch branches for dropdown
$branchQuery = "SELECT BranchID, BranchName FROM branch";
$branchResult = mysqli_query($conn, $branchQuery);

if (!$branchResult) {
    die("Error fetching branches: " . mysqli_error($conn));
}

$branchID = isset($_GET['BranchID']) ? intval($_GET['BranchID']) : 0;

if ($branchID === 0) {
    die("Invalid branch selected.");
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageName = trim($_POST['packageName']);
    $description = trim($_POST['description']); 
    $price = floatval($_POST['price']); // Ensure price is a valid float
    $branchID = intval($_POST['branchID']); // Ensure branchID is a valid integer

    // Validate inputs
    if (empty($packageName) || empty($description) || $price <= 0 || empty($branchID)) {
        $message = "All fields are required, and price must be greater than 0.";
    } else {
        // Insert query
        $query = "INSERT INTO package (PackageName, Description, Price, BranchID) 
                  VALUES ('$packageName', '$description', $price, $branchID)";
        
        // Execute query
        if (mysqli_query($conn, $query)) {
            $message = "Package added successfully!";
        } else {
            $message = "Error adding package: " . mysqli_error($conn);
        }
    }
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add New Package</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <h1 class="mb-4">Add New Package</h1>

    <?php if (!empty($message)): ?>
      <div class="alert alert-info" role="alert">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="packageName" class="form-label">Package Name</label>
        <input type="text" class="form-control" id="packageName" name="packageName" required>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label for="price" class="form-label">Price</label>
        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
      </div>
      <div class="mb-3">
        <label for="branchID" class="form-label">Branch</label>
        <select class="form-control" id="branchID" name="branchID" required>
          <option value="">-- Select Branch --</option>
          <?php while ($branch = mysqli_fetch_assoc($branchResult)): ?>
            <option value="<?php echo $branch['BranchID']; ?>">
              <?php echo htmlspecialchars($branch['BranchName']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-success">Add Package</button>
      <a href="managePack.php?BranchID=<?php echo $branchID; ?>" class="btn btn-secondary">Cancel</a>
      </form>
  </div>

  <input type="hidden" name="branchID" value="<?php echo $branchID; ?>">

</body>
</html>
