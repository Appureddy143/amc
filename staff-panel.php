<?php
session_start();
include('db-config.php'); // Include database connection

// Check if the user is logged in as staff
if ($_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

// Check if subject_code is set in the session
if (isset($_SESSION['subject_code'])) {
    $subject_code = $_SESSION['subject_code'];
    // Query to fetch question papers based on subject code
    $query = "SELECT * FROM question_papers WHERE subject_code = '$subject_code'";
} else {
    // If subject_code is not set, query to fetch all question papers
    $query = "SELECT * FROM question_papers";
}

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Panel</title>
    <link rel="stylesheet" href="styles.css">
    <style>
                        /* General Styles */
                        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }

        h2 {
            color: #444;
        }

        .navbar {
            background-color: #007bff;
            padding: 1em;
            display: flex;
            justify-content: flex-end;
            gap: 1em;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            padding: 0.5em 1em;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .navbar a:hover {
            background-color: #0056b3;
        }

        .content {
            padding: 2em;
            margin: 1em auto;
            max-width: 1200px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .staff-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        .staff-list th, .staff-list td {
            border: 1px solid #ddd;
            padding: 0.75em;
            text-align: left;
        }

        .staff-list th {
            background-color: #007bff;
            color: #fff;
        }

        .staff-list tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .staff-list tr:hover {
            background-color: #f1f1f1;
        }

        .pagination {
            margin: 1em 0;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #007bff;
            border-radius: 5px;
            color: #007bff;
        }

        .pagination a.active {
            background-color: #007bff;
            color: #fff;
        }

        .pagination a:hover {
            background-color: #0056b3;
            color: #fff;
        }

        /* Edit & Remove Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .remove-btn {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
        }

        .edit-btn {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .remove-btn:hover {
            background-color: #c82333;
        }

        .edit-btn:hover {
            background-color: #218838;
        }
        </style>
</head>
<body>
    <div class="navbar">
        <a href="logout.php">Logout</a>
        <a href="">Create Question Paper</a>
        <a href="view-timetable.php">View Timetables</a>
        <a href="enter-attendance.php">Attendance</a>
        <a href="enter-results.php">IA Enter</a>
    </div>

    <div class="content">
        <h2>Welcome to Staff Panel</h2>
        <p>Create and manage your allocated question papers.</p>

        <h3>Your Question Papers</h3>
        <div class="panel staff-panel">
            <table class="table">
                <tr>
                    <th>Title</th>
                    <th>Subject Code</th>
                    <th>Action</th>
                </tr>
                <?php
                // Loop through the result set and display question papers
                if ($result->num_rows > 0) {
                    while ($paper = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$paper['title']}</td>
                            <td>{$paper['subject_code']}</td>
                            <td><a href='edit-question.php?id={$paper['id']}'>Edit</a> | 
                            <a href='delete-question.php?id={$paper['id']}'>Delete</a></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No question papers found.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading">
        <div class="spinner"></div>
    </div>
</body>
</html>
