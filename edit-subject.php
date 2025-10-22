<?php
session_start();
include('db-config.php'); // Your PDO database connection

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if subject ID is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php"); // Redirect back to dashboard
    exit;
}

$subject_id = (int)$_GET['id'];
$error = '';
$subject = null;

try {
    // --- Handle form submission (Update subject details) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve and sanitize form data
        $name = trim($_POST['name']);
        $subject_code = trim($_POST['subject_code']);
        $branch = trim($_POST['branch']);
        $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);
        $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);

        // Basic validation
        if (empty($name) || empty($subject_code) || empty($branch) || $semester === false || $year === false || $semester < 1 || $semester > 8 || $year < 1 || $year > 4) {
             $error = "Please fill in all fields correctly.";
        } else {
            // Prepare and execute the UPDATE statement
            $update_sql = "UPDATE subjects SET name = ?, subject_code = ?, branch = ?, semester = ?, year = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if ($update_stmt->execute([$name, $subject_code, $branch, $semester, $year, $subject_id])) {
                // Redirect to the main dashboard on success
                header("Location: admin_dashboard.php?status=subject_updated");
                exit;
            } else {
                $error = "Error updating subject details. Please try again.";
            }
        }
    }

    // --- Fetch subject details to pre-fill the form ---
    $stmt = $conn->prepare("SELECT id, name, subject_code, branch, semester, year FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no subject is found with that ID, redirect
    if (!$subject) {
        header("Location: admin_dashboard.php");
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
    <title>Edit Subject</title>
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
            max-width: 500px; /* Slightly wider for more fields */
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
        button {
            margin-top: 25px; /* Increased margin */
            padding: 12px;
            background-color: #28a745; 
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
        <h2>Edit Subject Details</h2>

        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        
        <?php if ($subject): // Only show form if subject details exist ?>
            <form action="edit-subject.php?id=<?= $subject_id ?>" method="POST">
                
                <label for="name">Subject Name:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($subject['name']) ?>" required>

                <label for="subject_code">Subject Code:</label>
                <input type="text" id="subject_code" name="subject_code" value="<?= htmlspecialchars($subject['subject_code']) ?>" required>
                
                <label for="branch">Branch:</label>
                <select name="branch" id="branch" required>
                    <option value="CSE" <?= ($subject['branch'] == 'CSE') ? 'selected' : '' ?>>Computer Science</option>
                    <option value="ECE" <?= ($subject['branch'] == 'ECE') ? 'selected' : '' ?>>Electronics</option>
                    <option value="MECH" <?= ($subject['branch'] == 'MECH') ? 'selected' : '' ?>>Mechanical</option>
                    <option value="CIVIL" <?= ($subject['branch'] == 'CIVIL') ? 'selected' : '' ?>>Civil</option>
                </select>

                <label for="semester">Semester:</label>
                <input type="number" id="semester" name="semester" value="<?= htmlspecialchars($subject['semester']) ?>" min="1" max="8" required>

                <label for="year">Year:</label>
                <input type="number" id="year" name="year" value="<?= htmlspecialchars($subject['year']) ?>" min="1" max="4" required>

                <button type="submit">Update Subject</button>
            </form>
        <?php else: ?>
            <p>Could not find subject details.</p>
        <?php endif; ?>

        <a href="admin-panel.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</body>
</html>