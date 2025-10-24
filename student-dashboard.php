<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: /student-login'); // Redirect to login if not logged in
    exit;
}

// You can fetch student details here if needed
// require_once('db.php');
// $stmt = $pdo->prepare("SELECT student_name FROM students WHERE id = :id");
// $stmt->bindParam(':id', $_SESSION['student_id']);
// $stmt->execute();
// $student = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <style>
        /* Add some basic styles */
        body { font-family: sans-serif; padding: 20px; }
        .logout-btn { display: inline-block; padding: 10px; background-color: #d90429; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Welcome, Student!</h1>
    <p>Your Email: <?= htmlspecialchars($_SESSION['student_email']) ?></p>
    <p>This is your dashboard. More features coming soon.</p>
    <br>
    <a href="logout.php" class="logout-btn">Logout</a>
</body>
</html>
