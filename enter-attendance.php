<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

// Ensure only staff or HOD can access
$allowed_roles = ['staff', 'hod'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

// Determine the correct dashboard link based on role
$dashboard_link = match ($_SESSION['role']) {
    'staff' => 'staff_dashboard.php',
    'hod' => 'hod_dashboard.php',
    default => 'login.php', // Fallback
};

$students = []; // Initialize student list
$message = ""; // To store feedback messages
$message_type = "error"; // Default message type

try {
    // --- Fetch students for the dropdown menu using PDO ---
    $stmt_students = $conn->prepare("SELECT id, name FROM students ORDER BY name ASC");
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // --- Handle form submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Retrieve and validate form data
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $subject = trim($_POST['subject'] ?? ''); // Assuming subject is text for now
        $total_classes = filter_input(INPUT_POST, 'total_classes', FILTER_VALIDATE_INT);
        $attended_classes = filter_input(INPUT_POST, 'attended_classes', FILTER_VALIDATE_INT);

        // Basic validation
        if ($student_id === false || empty($subject) || $total_classes === false || $attended_classes === false) {
            $message = "Please fill in all fields correctly.";
        } elseif ($attended_classes > $total_classes) {
            $message = "Attended classes cannot be greater than total classes.";
        } elseif ($total_classes < 0 || $attended_classes < 0) {
             $message = "Class counts cannot be negative.";
        } else {
            // Use PostgreSQL's INSERT ... ON CONFLICT for upsert
            // Assumes a unique constraint exists on (student_id, subject)
            // If not, you might need separate SELECT then INSERT/UPDATE logic
            $sql = "INSERT INTO attendance (student_id, subject, total_classes, attended_classes) 
                    VALUES (?, ?, ?, ?)
                    ON CONFLICT (student_id, subject) 
                    DO UPDATE SET 
                        total_classes = EXCLUDED.total_classes, 
                        attended_classes = EXCLUDED.attended_classes";
            
            $stmt_upsert = $conn->prepare($sql);
            
            if ($stmt_upsert->execute([$student_id, $subject, $total_classes, $attended_classes])) {
                $message = "Attendance updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating attendance. Please check the data or ensure the student exists.";
            }
        }
    }

} catch (PDOException $e) {
    // Handle database errors
    $message = "Database Error: " . $e->getMessage();
     // In production, use a generic error:
     // $message = "A database error occurred. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Attendance</title>
    <style>
        /* Consistent Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-top: 0;
            margin-bottom: 25px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            text-align: left;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }
        input, select {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box; 
        }
        button[type="submit"] {
            margin-top: 25px;
            padding: 12px;
            background-color: #007bff; 
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        /* Message Styling */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            width: 100%; 
            box-sizing: border-box;
            text-align: center;
            font-size: 16px;
            border: 1px solid transparent;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
            text-align: center;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Enter/Update Student Attendance</h2>

        <?php 
        // Display feedback message if set
        if (!empty($message)) {
            echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>";
        } 
        ?>

        <form action="enter-attendance.php" method="POST">
            <label for="student_id">Student:</label>
            <select name="student_id" id="student_id" required>
                <option value="" disabled selected>-- Select Student --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                <?php endforeach; ?>
                 <?php if (empty($students)): ?>
                    <option value="" disabled>No students found.</option>
                <?php endif; ?>
            </select>

            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>

            <label for="total_classes">Total Classes Held:</label>
            <input type="number" id="total_classes" name="total_classes" min="0" required>

            <label for="attended_classes">Classes Attended:</label>
            <input type="number" id="attended_classes" name="attended_classes" min="0" required>

            <button type="submit">Submit Attendance</button>
        </form>
        
        <a href="<?= htmlspecialchars($dashboard_link) ?>" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>
```

**Important Note on `ON CONFLICT`:**

* The `INSERT ... ON CONFLICT ... DO UPDATE` command requires a **unique constraint** on the columns that define a conflict (in this case, likely `student_id` and `subject`).
* If you haven't already defined this unique constraint on your `attendance` table, you need to add it using your Neon SQL Editor:
    ```sql
    ALTER TABLE attendance
    ADD CONSTRAINT attendance_student_subject_unique UNIQUE (student_id, subject);
    
