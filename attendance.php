<?php
session_start();
// Use ../ to go up one directory to find the db-config.php file
include('../db-config.php'); 

// Check if a student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$attendance_records = []; // Initialize an empty array to hold the results

try {
    // Prepare and execute the database query using secure PDO methods
    $stmt = $conn->prepare("SELECT subject, total_classes, attended_classes FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    
    // Fetch all matching records into the array
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If the database query fails, stop the script and show a generic error
    // In a real application, you might log the specific error $e->getMessage()
    die("Error: Could not retrieve attendance data at this time. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            text-align: center; 
            background: #f4f7f6; 
            margin: 0;
            padding: 20px;
        }
        .container { 
            max-width: 800px; 
            margin: 40px auto; 
            background: white; 
            padding: 25px; 
            border-radius: 8px; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); 
        }
        h2 { 
            color: #333; 
            margin-bottom: 25px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: center; 
        }
        th { 
            background: #007bff; 
            color: white; 
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .back-link { 
            text-decoration: none; 
            color: white; 
            background: #6c757d; 
            padding: 10px 20px; 
            display: inline-block; 
            border-radius: 5px; 
            margin-top: 25px; 
            transition: background-color 0.3s;
        }
        .back-link:hover { 
            background: #5a6268; 
        }
        .no-records {
            color: #777;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>My Attendance Record</h2>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Total Classes</th>
                    <th>Attended Classes</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendance_records)): ?>
                    <tr>
                        <td colspan="4" class="no-records">No attendance records have been uploaded for you yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendance_records as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['subject']) ?></td>
                            <td><?= htmlspecialchars($row['total_classes']) ?></td>
                            <td><?= htmlspecialchars($row['attended_classes']) ?></td>
                            <td>
                                <?php
                                // Avoid division by zero if total_classes is 0
                                if ($row['total_classes'] > 0) {
                                    echo round(($row['attended_classes'] / $row['total_classes']) * 100, 2) . '%';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="student-dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>