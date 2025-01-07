<?php
session_start();

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Database connection
            $pdo = new PDO("mysql:host=localhost;dbname=rapidprint", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Query based on the selected role
            if ($role == 'Student') {
                $stmt = $pdo->prepare("SELECT * FROM student WHERE UserName = ?");
            } elseif ($role == 'Admin') {
                $stmt = $pdo->prepare("SELECT * FROM admin WHERE UserName = ?");
            } elseif ($role == 'Staff') {
                $stmt = $pdo->prepare("SELECT * FROM staff WHERE UserName = ?");
            } else {
                $error = "Invalid role selected.";
                $stmt = null;
            }

            if ($stmt) {
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify credentials
                if ($user && password_verify($password, $user['Password'])) {
                    // Set session variables
                    $_SESSION['username'] = $user['UserName'];
                    $_SESSION['phone'] = $user['PhoneNumber'];
                    $_SESSION['role'] = $role;

                    if ($role == 'Student') {
                        $_SESSION['student_logged_in'] = true; // Specific session flag for students
                        $_SESSION['studID'] = $user['studID']; // Store student ID
                        $_SESSION['student_name'] = $user['UserName']; // Store student name
                        $_SESSION['student_phone'] = $user['PhoneNumber']; // Store student phone
                        header('Location: dashboard.php'); // Redirect to the student dashboard
                        exit();
                    } elseif ($role == 'Admin') {
                        $_SESSION['adminID'] = $user['adminID'];
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_name'] = $user['UserName']; // Assuming 'UserName' is the admin's name
                        header('Location: adminDash.php');
                    } elseif ($role == 'Staff') {
                        $_SESSION['staff_logged_in'] = true; // Specific session flag for staff
                        $_SESSION['staffID'] = $user['staffID']; // Store staff ID
                        $_SESSION['staff_name'] = $user['UserName']; // Store staff name
                        header('Location: staffDash.php'); // Redirect to the staff dashboard
                    }
                    exit();
                } else {
                    $error = "Invalid username or password.";
                }
            }
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RP System</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="#" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="card shadow-lg p-4">
                        <div class="text-center mb-3">
                            <h4>Login</h4>
                        </div>

                        <!-- Error Message Placeholder -->
                        <div class="alert alert-danger text-center d-none" id="error-message"></div>

                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="" disabled selected>Select your role</option>
                                    <option value="Student">Student</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <a href="register.php" class="btn btn-link">Don't have an account? Register here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer id="footer" class="footer bg-dark text-white text-center py-3">
        <div class="container">
            <p>&copy; 2024 RP System. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>