<?php
session_start();
include('db-config.php'); 

// Ensure only admins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    // --- STUDENT DELETION LOGIC ---
    if (isset($_GET['delete_student'])) {
        $student_id = intval($_GET['delete_student']);

        $conn->beginTransaction();

        // Step 1: Delete related records first to maintain data integrity
        $stmt_attendance = $conn->prepare("DELETE FROM attendance WHERE student_id = ?");
        $stmt_attendance->execute([$student_id]);

        $stmt_results = $conn->prepare("DELETE FROM ia_results WHERE student_id = ?");
        $stmt_results->execute([$student_id]);
        
        // Step 2: Delete the student from the main students table
        $stmt_student = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt_student->execute([$student_id]);

        $conn->commit();

        // Redirect back to the student list to see the change
        header("Location: view-students.php?status=deleted");
        exit;
    }

    // --- DATA FETCHING FOR DISPLAY ---

    // Pagination setup
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $records_per_page = 10;
    $start_from = ($page - 1) * $records_per_page;

    // Fetch total student count for pagination
    $total_students_stmt = $conn->prepare("SELECT COUNT(*) FROM students");
    $total_students_stmt->execute();
    $total_students = $total_students_stmt->fetchColumn();
    $total_pages = ceil($total_students / $records_per_page);

    // Fetch student details for the current page (Corrected: removed 'branch')
    $student_stmt = $conn->prepare("SELECT id, usn, name, email FROM students ORDER BY id ASC LIMIT :limit OFFSET :offset");
    $student_stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $student_stmt->bindValue(':offset', $start_from, PDO::PARAM_INT);
    $student_stmt->execute();
    $students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: ". $e->getMessage());
}

// --- Initialize Serial Number for display ---
// This ensures the count continues correctly across pages
$current_sl_no = $start_from + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; transition: background-color 0.3s ease; }
        .navbar a:hover { background-color: #0056b3; }
        .container { width: 90%; max-width: 1200px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #444; border-bottom: 2px solid #eee; padding-bottom: 10px; }
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
        <a href="admin_dashboard.php">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>All Registered Students</h2>
        <table>
            <thead>
                <tr>
                    <th>Sl. No.</th> <!-- Changed from ID -->
                    <th>USN</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No students found. <a href="add-student.php">Add one now</a>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= $current_sl_no ?></td> <!-- Display serial number -->
                            <td><?= htmlspecialchars($student['usn']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td>
                                <!-- Actions still use the real, unique $student['id'] -->
                                <a href="edit-student.php?id=<?= $student['id'] ?>" class="action-btn edit-btn">Edit</a>
                                <a href="?delete_student=<?= $student['id'] ?>" class="action-btn remove-btn" onclick="return confirm('Are you sure you want to delete this student and all their records? This cannot be undone.')">Remove</a>
                            </td>
                        </tr>
                        <?php $current_sl_no++; // Increment serial number for the next row ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination Links -->
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>