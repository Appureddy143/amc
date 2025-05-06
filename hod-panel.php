<?php
session_start();
include('db-config.php');

// Debugging: Print session variables
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hod') {
    echo "Redirecting to login page...";
    header("Location: login.php");
    exit;
}

// Debugging - Check if branch is set
if (!isset($_SESSION['branch']) || empty($_SESSION['branch'])) {
    die("❌ Error: Branch not set for this HOD.");
}

$branch = $_SESSION['branch'];

// Fetch staff from the same branch
$query = "SELECT * FROM users WHERE role = 'staff' AND branch = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $branch);
$stmt->execute();
$result = $stmt->get_result();

// Check if query is returning any results
if ($result->num_rows === 0) {
    echo "No staff found for this branch.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Panel</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <a href="logout.php">Logout</a>
        <a href="create-question.php">Create Question Papers</a>
        <a href="create-timetable.php">Create Timetables</a>
        <a href="staff-list.php">View Staff</a>
    </div>
    <div class="content">
        <h2>Welcome to HOD Panel</h2>
        <p>Manage staff and question papers for your branch.</p>

        <h3>Staff Members</h3>
        <div class="panel hod-panel">
            <table class="table">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject Code</th>
                    <th>Action</th>
                </tr>
                <?php while ($staff = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($staff['first_name'] . " " . $staff['surname']) ?></td>
                        <td><?= htmlspecialchars($staff['email']) ?></td>
                        <td><?= isset($staff['subject_code']) ? htmlspecialchars($staff['subject_code']) : 'N/A' ?></td>
                        <td><a href='view-staff.php?id=<?= $staff['id'] ?>'>View</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading">
        <div class="spinner"></div>
    </div>
</body>
</html>
