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
    // --- USER MANAGEMENT LOGIC ---

    // Handle user deletion safely with prepared statements
    if (isset($_GET['delete_user'])) {
        $user_id = intval($_GET['delete_user']); 

        $conn->beginTransaction();

        // Step 1: Remove subject allocations for this user
        $stmt = $conn->prepare("DELETE FROM subject_allocation WHERE staff_id = ?");
        $stmt->execute([$user_id]);

        // Step 2: Now delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $conn->commit();

        header("Location: admin_dashboard.php");
        exit;
    }

    // --- SUBJECT MANAGEMENT LOGIC ---

    // Handle subject deletion
    if (isset($_GET['delete_subject'])) {
        $subject_id = intval($_GET['delete_subject']);

        $conn->beginTransaction();

        // Step 1: Remove any allocations of this subject
        $stmt = $conn->prepare("DELETE FROM subject_allocation WHERE subject_id = ?");
        $stmt->execute([$subject_id]);

        // Step 2: Delete the subject itself
        $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        
        $conn->commit();
        
        header("Location: admin_dashboard.php");
        exit;
    }


    // --- DATA FETCHING FOR DISPLAY ---

    // User Pagination
    $user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
    $records_per_page = 5; // Reduced for better viewing on one screen
    $user_start_from = ($user_page - 1) * $records_per_page;

    // Fetch total user count (excluding admins)
    $total_users_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $total_users_stmt->execute();
    $total_users = $total_users_stmt->fetchColumn();
    $total_user_pages = ceil($total_users / $records_per_page);

    // Fetch user details for the current page
    $user_stmt = $conn->prepare("SELECT id, first_name, surname, branch, email, created_at, role FROM users WHERE role != 'admin' ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $user_stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $user_stmt->bindValue(':offset', $user_start_from, PDO::PARAM_INT);
    $user_stmt->execute();
    $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Subject Pagination
    $subject_page = isset($_GET['subject_page']) ? (int)$_GET['subject_page'] : 1;
    $subject_start_from = ($subject_page - 1) * $records_per_page;

    // Fetch total subject count
    $total_subjects_stmt = $conn->prepare("SELECT COUNT(*) FROM subjects");
    $total_subjects_stmt->execute();
    $total_subjects = $total_subjects_stmt->fetchColumn();
    $total_subject_pages = ceil($total_subjects / $records_per_page);

    // Fetch subject details for the current page
    $subject_stmt = $conn->prepare("SELECT id, name, subject_code, branch, semester, year FROM subjects ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $subject_stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $subject_stmt->bindValue(':offset', $subject_start_from, PDO::PARAM_INT);
    $subject_stmt->execute();
    $subjects = $subject_stmt->fetchAll(PDO::FETCH_ASSOC);


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
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; transition: background-color 0.3s ease; }
        .navbar a:hover { background-color: #0056b3; }
        .container { width: 90%; max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #444; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #ddd; padding: 0.75em; text-align: left; }
        th { background-color: #007bff; color: #fff; }
        .pagination { margin: 1em 0; text-align: center; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 5px 10px; border: 1px solid #007bff; border-radius: 5px; color: #007bff; }
        .pagination a.active { background-color: #007bff; color: #fff; }
        .pagination a:hover { background-color: #0056b3; color: #fff; }
        .action-btn { text-decoration: none; padding: 5px 10px; border-radius: 5px; font-size: 14px; color: white; margin-right: 5px; display: inline-block; }
        .edit-btn { background-color: #28a745; }
        .remove-btn { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="add-staff.php">Add Staff</a>
        <a href="add-student.php">Add Student</a>
        <a href="add-subject.php">Add Subjects</a>
        <a href="bulk-upload.php">Bulk Upload</a>
        <a href="generate-paper.php">Generate Paper</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Admin Dashboard</h2>
        
        <!-- Users Section -->
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
                            <a href="?delete_user=<?= $user['id'] ?>" class="action-btn remove-btn" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')">Remove</a>
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
            <?php for ($i = 1; $i <= $total_user_pages; $i++): ?>
                <a href="?user_page=<?= $i ?>" <?= $i === $user_page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </div>

        <hr style="margin: 40px 0;">

        <!-- Subjects Section -->
        <h2>All Subjects</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Subject Name</th>
                    <th>Subject Code</th>
                    <th>Branch</th>
                    <th>Semester</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject['id']) ?></td>
                        <td><?= htmlspecialchars($subject['name']) ?></td>
                        <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                        <td><?= htmlspecialchars($subject['branch']) ?></td>
                        <td><?= htmlspecialchars($subject['semester']) ?></td>
                        <td><?= htmlspecialchars($subject['year']) ?></td>
                        <td>
                            <a href="edit-subject.php?id=<?= $subject['id'] ?>" class="action-btn edit-btn">Edit</a>
                            <a href="?delete_subject=<?= $subject['id'] ?>" class="action-btn remove-btn" onclick="return confirm('Are you sure you want to delete this subject? This cannot be undone.')">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No subjects found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_subject_pages; $i++): ?>
                <a href="?subject_page=<?= $i ?>" <?= $i === $subject_page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>