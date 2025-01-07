<?php

session_start();

// Check if the student is logged in
if (!isset($_SESSION['studID']) || empty($_SESSION['studID'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "rapidprint");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$student = [];
$studID = $_SESSION['studID']; // Assuming student ID is stored in session

// Fetch student data
$stmt = "SELECT UserName, PhoneNumber, email, address, StudentCard FROM Student WHERE studID = $studID";
$result = mysqli_query($conn, $stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $student = mysqli_fetch_assoc($result);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $upload_error = "";

    // file upload
    $target_dir = __DIR__ . "/uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); 
    }

    $target_file = $target_dir . basename($_FILES["student_card"]["name"]);
    $upload_ok = true;

    if (!empty($_FILES["student_card"]["name"])) {


        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'png', 'jpeg', 'pdf'];
        if (!in_array($file_type, $allowed_types)) {
            $upload_ok = false;
            $upload_error = "Only JPG, PNG, JPEG, and PDF files are allowed.";
        }

        // Move the uploaded file
        if ($upload_ok) {
            if (move_uploaded_file($_FILES["student_card"]["tmp_name"], $target_file)) {
                $stmt = "UPDATE Student SET StudentCard = '" . basename($_FILES["student_card"]["name"]) . "' WHERE studID = $studID";
                mysqli_query($conn, $stmt);
            } else {
                $upload_ok = false;
                $upload_error = "Failed to upload file.";
            }
        }
    }

    // Update student details
    if ($upload_ok) {
        $stmt = "UPDATE Student SET PhoneNumber = '$phone', email = '$email', address = '$address' WHERE studID = $studID";
        if (mysqli_query($conn, $stmt)) {
            $message = "Information updated successfully.";
        } else {
            $message = "Failed to update information: " . mysqli_error($conn);
        }
    } else {
        $message = $upload_error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Information</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
<header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="dashboard.php" class="logo d-flex align-items-center">
                <h1 class="sitename">RP</h1>
                <span>.</span>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="Dashboard.php">Dashboard</a></li>
                    <li><a href="membership-card.php" >Membership Card</a></li>
                    <li><a href="update-student-info.php" class="active">Manage Profile</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
            <a href="logout.php" class="btn btn-primary btn-sm">Log Out</a>
        </div>
    </header>

    <main class="main bg-light py-5">
        <div class="container">
            <h2 class="mb-4">Update Your Information</h2>

            <?php if (!empty($message)) : ?>
                <div class="alert alert-info">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($student)) : ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Display username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($student['UserName']); ?>" disabled>
                    </div>

                    <!-- Editable fields -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($student['PhoneNumber']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($student['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" required><?= htmlspecialchars($student['address']); ?></textarea>
                    </div>

                    <!-- File upload -->
                    <div class="mb-3">
                        <label for="student_card" class="form-label">Upload Student Card</label>
                        <input type="file" class="form-control" id="student_card" name="student_card">
                        <?php if (!empty($student['StudentCard'])) : ?>
                            <p class="mt-2">Current File: 
                                <a href="uploads/<?= htmlspecialchars($student['StudentCard']); ?>" target="_blank">
                                    <?= htmlspecialchars($student['StudentCard']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Information</button>
                </form>
            <?php else : ?>
                <p>No student data found.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
