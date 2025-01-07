<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "rapidprint");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $role = trim($_POST['role']);

    // Check for empty fields
    if (empty($username) || empty($password) || empty($phone) || empty($role)) {
        $message = "All fields are required.";
    } else {
        // Encrypt the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($role === "Student") {
            // Insert user into the `student` table
            $stmt = $conn->prepare("INSERT INTO student (UserName, Password, PhoneNumber) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $phone);
        } elseif ($role === "Admin") {
            // Insert user into the `admin` table
            $stmt = $conn->prepare("INSERT INTO admin (UserName, Password, PhoneNumber) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $phone);
        } elseif ($role === "Staff") {
            // Insert user into the `staff` table
            $stmt = $conn->prepare("INSERT INTO staff (UserName, Password, PhoneNumber) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $phone);
        } else {
            $message = "Invalid role selected.";
            $stmt = null;
        }

        if ($stmt && $stmt->execute()) {
            $message = ucfirst($role) . " created successfully!";
        } elseif ($stmt) {
            $message = "Error: " . $conn->error;
        }

        if ($stmt) {
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Create User - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="create-user-page">
    <main class="main">
        <section id="create-user" class="create-user section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-8">
                        <div class="card shadow-lg p-4">
                            <div class="text-center mb-3">
                                <h4>Create New User</h4>
                            </div>

                            <!-- Success/Error Message -->
                            <?php if (!empty($message)) : ?>
                                <div class="alert alert-info text-center">
                                    <?= htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Create User Form -->
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter Username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter Phone Number" required>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="Student">Student</option>
                                        <option value="Admin">Admin</option>
                                        <option value="Staff">Staff</option>
                                    </select>
                                </div>
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary">Create User</button>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <a href="dashboard.php" class="btn btn-link">Go to Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
