<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

// Ensure only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Wrap database operations in a try-catch block for error handling
try {
    // Handle user deletion safely with prepared statements
    if (isset($_GET['delete'])) {
        $user_id = intval($_GET['delete']); // Ensure it's an integer

        // Use a transaction to ensure both queries succeed or fail together
        $conn->beginTransaction();

        // Step 1: Remove subject allocations for this user
        $stmt = $conn->prepare("DELETE FROM subject_allocation WHERE staff_id = ?");
        $stmt->execute([$user_id]);

        // Step 2: Now delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $conn->commit(); // Commit the transaction

        header("Location: admin_dashboard.php"); // Redirect to the correct filename
        exit;
    }

    // Pagination setup
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 10;
    $start_from = ($page - 1) * $records_per_page;

    // Fetch total user count (excluding admins) using PDO
    $total_records_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $total_records_stmt->execute();
    $total_records = $total_records_stmt->fetchColumn(); // Efficient way to get a single value
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch user details for the current page (excluding admins) using PDO
    // PostgreSQL uses LIMIT... OFFSET... syntax
    $user_stmt = $conn->prepare("SELECT id, first_name, surname, branch, email, created_at, role FROM users WHERE role != 'admin' ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $user_stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $user_stmt->bindValue(':offset', $start_from, PDO::PARAM_INT);
    $user_stmt->execute();
    $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If any database error occurs, stop the script and show a generic error
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
        .action-btn {
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            margin-right: 5px;
        }

        .edit-btn {
            background-color: #28a745;
        }

        .remove-btn {
            background-color: #dc3545;
        }

    </style>
</head>
<body>
    <div class="navbar">
        <a href="logout.php">Logout</a>
        <a href="add-staff.php">Add Staff</a>
        <a href="add-student.php">Add Student</a>
        <a href="add-subject.php">Add Subjects</a>
        <a href="subject-allocation.php">Allocate Subjects</a>
        <a href="create-timetable.php">Create Timetables</a>
    </div>

    <div class="container">
        <h2>Admin Dashboard</h2>
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
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['surname']) ?></td>
                        <td><?= htmlspecialchars($user['branch'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars(date('d-m-Y', strtotime($user['created_at']))) ?></td>
                        <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                        <td>
                            <a href="edit-staff.php?id=<?= $user['id'] ?>" class="action-btn edit-btn">Edit</a>
                            <a href="admin_dashboard.php?delete=<?= $user['id'] ?>" class="action-btn remove-btn" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">&laquo; Previous</a><?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><a href="?page=<?= $page + 1 ?>">Next &raquo;</a><?php endif; ?>
        </div>
    </div>
</body>
</html>