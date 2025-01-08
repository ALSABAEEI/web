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

// Initialize variables
$branchID = isset($_GET['BranchID']) ? intval($_GET['BranchID']) : 0;
$message = "";

// Fetch branch details for pre-filling the form
if ($branchID > 0) {
  $query = "SELECT BranchName, Location, ContactInfo FROM branch WHERE BranchID = $branchID"; 
  $result = mysqli_query($conn, $query);

  if ($result && mysqli_num_rows($result) > 0) {
      $branch = mysqli_fetch_assoc($result);
  } else {
      die("Branch not found.");
  }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $branchName = trim($_POST['branchName']); 
  $location = trim($_POST['location']);    
  $contactInfo = trim($_POST['contactInfo']); 

  // Validate inputs
  if (empty($branchName) || empty($location) || empty($contactInfo)) {
      $message = "All fields are required.";
  } else {
      // Update query
      $query = "UPDATE branch 
                SET BranchName = '$branchName', Location = '$location', ContactInfo = '$contactInfo' 
                WHERE BranchID = $branchID"; // Direct query

      if (mysqli_query($conn, $query)) {
          $message = "Branch updated successfully!";
      } else {
          $message = "Error updating branch: " . mysqli_error($conn);
      }
  }
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Update Branch</title>
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
    <h1 class="mb-4">Update Branch</h1>

    <?php if (!empty($message)): ?>
      <div class="alert alert-info" role="alert">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="branchName" class="form-label">Branch Name</label>
        <input type="text" class="form-control" id="branchName" name="branchName" value="<?php echo htmlspecialchars($branch['BranchName']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="location" class="form-label">Location</label>
        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($branch['Location']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="contactInfo" class="form-label">Contact Info</label>
        <input type="text" class="form-control" id="contactInfo" name="contactInfo" value="<?php echo htmlspecialchars($branch['ContactInfo']); ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Update Branch</button>
      <a href="manageBran.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</body>
</html>
