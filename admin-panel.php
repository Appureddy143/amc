<?php
session_start();
include('db-config.php'); // Include database connection

// Ensure only admins can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle user deletion safely
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);

    // Step 1: Remove subject allocations for this user
    $conn->query("DELETE FROM subject_allocation WHERE staff_id = $user_id");

    // Step 2: Now delete the user
    $conn->query("DELETE FROM users WHERE id = $user_id");
    
    header("Location: admin-panel.php");
    exit;
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$start_from = ($page - 1) * $records_per_page;

// Fetch total user count excluding admins
$total_records_query = "SELECT COUNT(*) AS total FROM users WHERE role != 'admin'"; // Exclude admins
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch user details for the current page excluding admins
$user_query = "SELECT id, first_name, surname, branch, email, created_at, role FROM users WHERE role != 'admin' LIMIT $start_from, $records_per_page"; // Exclude admins
$user_result = $conn->query($user_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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

        .container {
            width: 80%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 0.75em;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: #fff;
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
        .edit-btn, .remove-btn {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
        }

        .edit-btn {
            background-color: #28a745;
            color: white;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
        }

        .remove-btn:hover {
            background-color: #c82333;
        }

        .edit-btn:hover {
            background-color: #218838;
        }
    </style>
</head <body>
    <div class="navbar">
        <a href="logout.php">Logout</a>
        <a href="add-staff.php">Add Staff </a>
        <a href="add-student.php">Add Student</a>
        <a href="add-subject.php">Add Subjects</a>
        <a href="subject-allocation.php">Allocate Subjects</a>
        <a href="create-timetable.php">Create Timetables</a>
    </div>

    <div class="container">
        <h2>Admin Panel</h2>
        <p>Manage users, allocate subjects, and create exam schedules.</p>

        <h2>All Users (Excluding Admins)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Email</th>
                    <th>Joining Date</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $user_result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['surname']) ?></td>
                        <td><?= htmlspecialchars($user['branch']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= date('d-m-Y', strtotime($user['created_at'])) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <a href="edit-staff.php?id=<?= $user['id'] ?>" class="edit-btn">Edit</a>
                            <a href="admin-panel.php?delete=<?= $user['id'] ?>" class="remove-btn" onclick="return confirm('Are you sure?')">Remove</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1) { ?><a href="?page=<?= $page - 1 ?>">&laquo; Previous</a><?php } ?>
            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php } ?>
            <?php if ($page < $total_pages) { ?><a href="?page=<?= $page + 1 ?>">Next &raquo;</a><?php } ?>
        </div>
    </div>
</body>
</html>