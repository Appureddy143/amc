<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit;
}

include('db-config.php');
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT subject, total_classes, attended_classes FROM attendance WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; background: #f4f4f4; }
        .container { width: 50%; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #28a745; color: white; }
        a { text-decoration: none; color: white; background: red; padding: 10px; display: inline-block; border-radius: 5px; margin-top: 20px; }
        a:hover { background: darkred; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Attendance</h2>
        <table>
            <tr>
                <th>Subject</th>
                <th>Total Classes</th>
                <th>Attended Classes</th>
                <th>Attendance %</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= htmlspecialchars($row['total_classes']) ?></td>
                    <td><?= htmlspecialchars($row['attended_classes']) ?></td>
                    <td><?= round(($row['attended_classes'] / $row['total_classes']) * 100, 2) ?>%</td>
                </tr>
            <?php endwhile; ?>
        </table>
        <a href="student-dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>
