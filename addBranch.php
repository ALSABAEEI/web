<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// Inline database connection 123132132122
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rapidprint";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

// Fetch all admin names for the dropdown
$adminQuery = "SELECT adminID, UserName FROM admin";
$adminResult = mysqli_query($conn, $adminQuery);
if (!$adminResult) {
  die("Error fetching admins: " . mysqli_error($conn));
}

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $adminID = intval($_POST['adminID']); // Ensure adminID is a valid integer
  $branchName = trim($_POST['branchName']); // Remove extra spaces
  $location = trim($_POST['location']); // Remove extra spaces
  $contactInfo = trim($_POST['contactInfo']); // Remove extra spaces

  // Validate inputs
  if (empty($adminID) || empty($branchName) || empty($location) || empty($contactInfo)) {
    $message = "All fields are required.";
  } else {
    // Insert query
    $query = "INSERT INTO branch (adminID, BranchName, Location, ContactInfo) 
                  VALUES ($adminID, '$branchName', '$location', '$contactInfo')";

    // Execute query
    if (mysqli_query($conn, $query)) {
      $message = "Branch added successfully!";
    } else {
      $message = "Error adding branch: " . mysqli_error($conn);
    }
  }
}

?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add New Branch</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <div class="container mt-5">
    <h1 class="mb-4">Add New Branch</h1>

    <?php if (!empty($message)): ?>
      <div class="alert alert-info" role="alert">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="adminID" class="form-label">Admin Name</label>
        <select class="form-control" id="adminID" name="adminID" required>
          <option value="">-- Select Admin --</option>
          <?php while ($adminRow = mysqli_fetch_assoc($adminResult)): ?>
            <option value="<?php echo $adminRow['adminID']; ?>">
              <?php echo htmlspecialchars($adminRow['UserName']); ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="branchName" class="form-label">Branch Name</label>
        <input type="text" class="form-control" id="branchName" name="branchName" required>
      </div>
      <div class="mb-3">
        <label for="location" class="form-label">Location</label>
        <input type="text" class="form-control" id="location" name="location" required>
      </div>
      <div class="mb-3">
        <label for="contactInfo" class="form-label">Contact Info</label>
        <input type="text" class="form-control" id="contactInfo" name="contactInfo" required>
      </div>
      <button type="submit" class="btn btn-success">Add Branch</button>
      <a href="manageBran.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</body>

</html>