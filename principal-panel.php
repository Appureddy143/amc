<?php
session_start();
include('db-config.php'); // Include database connection

// Check if the user is logged in as Principal
if ($_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit;
}

// Fetch all data for staff and HOD
$query = "SELECT * FROM users WHERE role IN ('staff', 'hod')";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <a href="logout.php">Logout</a>
        <a href="manage-users.php">Manage Users</a>
        <a href="view-reports.php">View Reports</a>
    </div>
    <div class="content">
        <h2>Welcome to Principal Panel</h2>
        <p>Manage all departments and staff.</p>

        <h3>Staff & HOD Members</h3>
        <div class="panel principal-panel">
            <table class="table">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                <?php
                while ($user = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$user['first_name']} {$user['surname']}</td>
                        <td>{$user['email']}</td>
                        <td>{$user['role']}</td>
                        <td><a href='view-user.php?id={$user['id']}'>View</a></td>
                    </tr>";
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
