<?php

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Database 
$conn = new mysqli("localhost", "root", "", "rapidprint");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//  adding a new student
if (isset($_POST['add_student'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    if (empty($username) || empty($password) || empty($phone) || empty($address) || empty($email)) {
        $message = "All fields are required.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = "INSERT INTO Student (UserName, Password, PhoneNumber, Address, Email) 
                 VALUES ('$username', '$hashed_password', '$phone', '$address', '$email')";

        $result = mysqli_query($conn, $stmt);

        if ($result) {
            header("Location: manage-students.php?success=1");
            exit();
        } else {
            $message = "Error adding student.";
        }
    }
}

//  deleting student
if (isset($_POST['delete_student'])) {
    $studID = intval($_POST['studID']);

    $stmt = "DELETE FROM Student WHERE studID = $studID";
    $result = mysqli_query($conn, $stmt);

    if ($result) {
        header("Location: manage-students.php?success=1");
        exit();
    } else {
        $message = "Error deleting student.";
    }
}

// Fetch all students
$stmt = "SELECT studID, UserName, PhoneNumber, Address, Email, StudentCard, IsVerified FROM Student";
$result = mysqli_query($conn, $stmt);
$students = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage-students.php" class="active">Manage Students</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main bg-light py-5">
        <div class="container">
            <!-- Success/Error Message -->
            <?php if (isset($_GET['success'])) : ?>
                <div class="alert alert-success">
                    <?= ($_GET['success'] == 1) ? "Action completed successfully!" : ""; ?>
                </div>
            <?php elseif (!empty($message)) : ?>
                <div class="alert alert-info">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- List of Students -->
            <section class="mb-5">
                <h2 class="mb-3">All Students</h2>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Student Card</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student) : ?>
                            <tr>
                                <td><?= htmlspecialchars($student['studID']); ?></td>
                                <td><?= htmlspecialchars($student['UserName']); ?></td>
                                <td><?= htmlspecialchars($student['PhoneNumber']); ?></td>
                                <td><?= htmlspecialchars($student['Address']); ?></td>
                                <td><?= htmlspecialchars($student['Email']); ?></td>
                                <td><?= htmlspecialchars($student['StudentCard']); ?></td>
                                <td>
                                    <?php if ($student['IsVerified'] == 1) : ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else : ?>
                                        <span class="badge bg-danger">Not Verified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="Admin-update-student.php?studID=<?= $student['studID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                    <form method="POST" action="" class="d-inline">
                                        <input type="hidden" name="studID" value="<?= $student['studID']; ?>">
                                        <button type="submit" name="delete_student" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- Add New Student Form -->
            <section>
                <h2 class="mb-3">Add New Student</h2>
                <form method="POST" action="">
                    <input type="hidden" name="add_student" value="1">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </form>
            </section>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
