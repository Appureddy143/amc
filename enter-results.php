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
    $marks = $_POST['marks'];

    // Insert or update IA results
    $stmt = $conn->prepare("INSERT INTO ia_results (student_id, subject, marks) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE marks = VALUES(marks)");
    $stmt->bind_param("isi", $student_id, $subject, $marks);
    
    if ($stmt->execute()) {
        $message = "IA Results updated successfully!";
    } else {
        $message = "Error updating results!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter IA Results</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Enter IA Results</h2>
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

        <label>Marks:</label>
        <input type="number" name="marks" min="0" max="100" required>

        <button type="submit">Submit</button>
    </form>

    <a href="staff-dashboard.php">Back to Dashboard</a>
</body>
</html>
