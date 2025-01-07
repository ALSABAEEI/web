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

$message = "";
$student = [];

if (isset($_GET['studID'])) {
    $studID = $_GET['studID'];

    // all the student data
    $stmt = "SELECT studID, UserName, PhoneNumber, email, address, StudentCard, isVerified FROM Student WHERE studID = $studID";
    $result = mysqli_query($conn, $stmt);

    if ($result) {
        $student = mysqli_fetch_assoc($result);
    }
}

//  form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studID = $_POST['studID'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $verify = isset($_POST['verify']) ? 1 : 0;

    // Update student 
    $stmt = "UPDATE Student SET PhoneNumber = '$phone', email = '$email', address = '$address', isVerified = $verify WHERE studID = $studID";
    if (mysqli_query($conn, $stmt)) {
        $message = "Student information updated successfully!";
        header("Location: manage-students.php?success=1");
        exit();
    } else {
        $message = "Failed to update information: " . mysqli_error($conn);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student</title>
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
            <a href="manage-students.php" class="btn btn-secondary btn-sm">Back to Students</a>
        </div>
    </header>

    <main class="main bg-light py-5">
        <div class="container">
            <h2 class="mb-4">Update Student</h2>

            <?php if (!empty($message)) : ?>
                <div class="alert alert-info">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($student)) : ?>
                <form method="POST" action="">
                    <input type="hidden" name="studID" value="<?= htmlspecialchars($student['studID']); ?>">

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

                    <!-- Student Card Display -->
                    <div class="mb-3">
                        <label for="student_card" class="form-label">Student Card</label>
                        <?php if (!empty($student['StudentCard'])) : ?>
                            <p>Current Student Card: 
                                <a href="uploads/<?= htmlspecialchars($student['StudentCard']); ?>" target="_blank">
                                    <?= htmlspecialchars($student['StudentCard']); ?>
                                </a>
                            </p>
                        <?php else : ?>
                            <p>No student card uploaded.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Verification Checkbox -->
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="verify" name="verify" <?= $student['isVerified'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="verify">Verify Student</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Student</button>
                </form>
            <?php else : ?>
                <p>No student data found.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
