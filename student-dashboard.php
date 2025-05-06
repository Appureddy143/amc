<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit;
}

include('db-config.php');
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT name, email, dob, address FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
        }
        p {
            font-size: 18px;
        }
        .btn {
            display: block;
            width: 60%;
            margin: 10px auto;
            padding: 10px;
            font-size: 18px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .logout {
            background: red;
        }
        .logout:hover {
            background: darkred;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?= htmlspecialchars($student['name']) ?></h2>
        <p>Email: <?= htmlspecialchars($student['email']) ?></p>
        <p>Date of Birth: <?= htmlspecialchars($student['dob']) ?></p>
        <p>Address: <?= htmlspecialchars($student['address']) ?></p>

        <a href="ia-results.php" class="btn">View IA Results</a>
        <a href="attendance.php" class="btn">View Attendance</a>
        <a href="stlogout.php" class="btn logout">Logout</a>
    </div>
</body>
</html>
