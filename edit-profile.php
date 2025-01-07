<?php
session_start();

// Check if the student is logged in
if (!isset($_SESSION['studID']) || empty($_SESSION['studID'])) {
    header('Location: login.php');
    exit();
}

// Fetch student ID from the session
$studID = $_SESSION['studID'];

// Initialize error message
$error = "";

// Connect to the database
try {
    $pdo = new PDO("mysql:host=localhost;dbname=rapidprint", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch current student details
    $stmt = $pdo->prepare("SELECT * FROM Student WHERE studID = ?");
    $stmt->execute([$studID]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get updated values from the form
        $username = $_POST['username'];
        $email = $_POST['email'];
        $phoneNumber = $_POST['phoneNumber'];
        $password = $_POST['password'];

        // Validate inputs (basic validation for empty fields)
        if (empty($username) || empty($email) || empty($phoneNumber) || empty($password)) {
            $error = "All fields are required.";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Update the student details in the database
            $updateStmt = $pdo->prepare("UPDATE Student SET UserName = ?, Email = ?, PhoneNumber = ?, Password = ? WHERE studID = ?");
            $updateStmt->execute([$username, $email, $phoneNumber, $hashedPassword, $studID]);

            // Redirect to dashboard with success message
            $_SESSION['success_message'] = "Profile updated successfully!";
            header('Location: dashboard.php');
            exit();
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Edit Profile - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="starter-page-page">

    <!-- Header -->
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main bg-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-4">
                    <h1>Edit Profile</h1>
                    <p>Update your profile details below.</p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="row gy-4">
                <div class="col-lg-6">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($student['UserName']); ?>" required>
                </div>

                <div class="col-lg-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($student['Email']); ?>" required>
                </div>

                <div class="col-lg-6">
                    <label for="phoneNumber" class="form-label">Phone Number</label>
                    <input type="text" id="phoneNumber" name="phoneNumber" class="form-control" value="<?= htmlspecialchars($student['PhoneNumber']); ?>" required>
                </div>

                <div class="col-lg-6">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="col-lg-12 text-center">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer id="footer" class="footer bg-dark text-white py-3 mt-5">
        <div class="container text-center">
            <p>© 2024 RP System. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
