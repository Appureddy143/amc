<?php
session_start();
include('db-config.php');

// Check if the user is logged in as admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usn = isset($_POST['usn']) ? trim($_POST['usn']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $password = isset($_POST['password']) ? password_hash(trim($_POST['password']), PASSWORD_DEFAULT) : '';

    // Ensure required fields are not empty
    if (!empty($usn) && !empty($name) && !empty($email) && !empty($dob) && !empty($password)) {
        // Check if USN already exists
        $check_stmt = $conn->prepare("SELECT usn FROM students WHERE usn = ?");
        $check_stmt->bind_param("s", $usn);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "<p style='color:red;'>Error: USN already exists!</p>";
        } else {
            // Insert student into students table
            $stmt = $conn->prepare("INSERT INTO students (usn, name, email, dob, address, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $usn, $name, $email, $dob, $address, $password);

            if ($stmt->execute()) {
                $message = "<p style='color:green;'>Student added successfully!</p>";
            } else {
                $message = "<p style='color:red;'>Error: " . $conn->error . "</p>";
            }
        }
    } else {
        $message = "<p style='color:red;'>All fields are required!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2> </h2>
    <?php if (isset($message)) echo $message; ?>

    <form method="POST">

    </form>
</body>
</html>
