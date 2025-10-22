<?php
session_start();
include('db-config.php'); // Your PDO database connection

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if student ID is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: view-students.php"); // Redirect to student list
    exit;
}

$student_id = (int)$_GET['id'];
$error = '';
$student = null;

try {
    // --- Handle form submission (Update student details) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usn = trim($_POST['usn']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        // Prepare and execute the UPDATE statement
        $update_sql = "UPDATE students SET usn = ?, name = ?, email = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt->execute([$usn, $name, $email, $student_id])) {
            // Redirect to the student list on success
            header("Location: view-students.php?status=updated");
            exit;
        } else {
            $error = "Error updating student details. Please try again.";
        }
    }

    // --- Fetch student details to pre-fill the form ---
    $stmt = $conn->prepare("SELECT id, usn, name, email FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no student is found with that ID, redirect
    if (!$student) {
        header("Location: view-students.php");
        exit;
    }

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
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
            max-width: 450px;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-top: 0;
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
            box-sizing: border-box; /* Ensures padding doesn't affect width */
        }
        button {
            margin-top: 20px;
            padding: 12px;
            background-color: #28a745; /* Green for update */
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background-color: #218838;
        }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 10px;
        }
        .back-link {
            display: block;
            margin-top: 15px;
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
        <h2>Edit Student Details</h2>

        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        
        <?php if ($student): // Only show the form if student details were successfully fetched ?>
            <form action="edit-student.php?id=<?= $student_id ?>" method="POST">
                
                <label for="usn">USN:</label>
                <input type="text" id="usn" name="usn" value="<?= htmlspecialchars($student['usn']) ?>" required>
                
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>

                <button type="submit">Update Details</button>
            </form>
        <?php else: ?>
            <p>Could not find student details.</p>
        <?php endif; ?>

        <a href="view-students.php" class="back-link">Back to View Students</a>
    </div>
</body>
</html>