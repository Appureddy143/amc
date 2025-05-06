<?php
session_start();
include('db-config.php');

// Check if the user is logged in as staff or HOD
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'HOD')) {
    header("Location: login.php");
    exit;
}

// Fetch students for selection
$students = $conn->query("SELECT id, full_name FROM students");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $subject = $_POST['subject'];
    $total_classes = $_POST['total_classes'];
    $attended_classes = $_POST['attended_classes'];

    // Insert or update attendance
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, subject, total_classes, attended_classes) 
                            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
                            total_classes = VALUES(total_classes), attended_classes = VALUES(attended_classes)");
    $stmt->bind_param("isii", $student_id, $subject, $total_classes, $attended_classes);
    
    if ($stmt->execute()) {
        $message = "Attendance updated successfully!";
    } else {
        $message = "Error updating attendance!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Attendance</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Enter Attendance</h2>
    <?php if (isset($message)) echo "<p style='color:green;'>$message</p>"; ?>
    
    <form method="POST">
        <label>Student:</label>
        <select name="student_id" required>
            <?php while ($row = $students->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Subject:</label>
        <input type="text" name="subject" required>

        <label>Total Classes:</label>
        <input type="number" name="total_classes" required>

        <label>Attended Classes:</label>
        <input type="number" name="attended_classes" required>

        <button type="submit">Submit</button>
    </form>

    <a href="staff-dashboard.php">Back to Dashboard</a>
</body>
</html>
