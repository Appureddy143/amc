<?php
session_start();
include('db-config.php'); // Database connection

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Store selected semester and year in session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['go'])) {
    $_SESSION['selectedSemester'] = $_POST['semester'] ?? '';
    $_SESSION['selectedYear'] = $_POST['year'] ?? '';
    header("Location: create-timetable-form.php");
    exit;
}

$semesters = range(1, 8);
$years = [];
for ($y = 2020; $y <= 2040; $y++) {
    $years[] = "$y-" . ($y + 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Semester and Year</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <a href="admin-panel.php">Back to Admin Panel</a>
    </div>
    <div class="content">
        <h2>Select Semester and Year</h2>
        <form action="create-timetable.php" method="POST">
            <label for="semester">Select Semester:</label>
            <select name="semester" required>
                <option value="">Select Semester</option>
                <?php foreach ($semesters as $sem) { ?>
                    <option value="<?= $sem ?>">
                        Semester <?= $sem ?>
                    </option>
                <?php } ?>
            </select>

            <label for="year">Select Year:</label>
            <select name="year" required>
                <option value="">Select Year</option>
                <?php foreach ($years as $year) { ?>
                    <option value="<?= $year ?>">
                        Year <?= $year ?>
                    </option>
                <?php } ?>
            </select>
            <button type="submit" name="go">Go</button>
        </form>
    </div>
</body>
</html>

<!-- CSS (in styles.css) -->
<style>
/* General Page Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
}

.navbar {
    background-color: #333;
    padding: 10px;
    text-align: center;
}

.navbar a {
    color: white;
    text-decoration: none;
    font-size: 18px;
}

.content {
    width: 60%; /* Set the width of the content */
    max-width: 800px; /* Optional: Maximum width to make it more responsive */
    margin: 40px auto; /* Center the content and add some space from top */
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Style for form elements */
label {
    font-size: 16px;
    margin-bottom: 10px;
    display: block;
}

select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
}

button:hover {
    background-color: #45a049;
}
</style>
