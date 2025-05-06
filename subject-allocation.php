<?php
session_start();
include('db-config.php'); // Database connection

// Check if user is logged in as admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle subject allocation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'];
    $subject_ids = $_POST['subject_ids']; // Array of subject IDs

    foreach ($subject_ids as $subject_id) {
        $allocation_query = "
            INSERT INTO subject_allocation (staff_id, subject_id) 
            SELECT '$staff_id', '$subject_id'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT * 
                FROM subject_allocation 
                WHERE staff_id = '$staff_id' AND subject_id = '$subject_id'
            )
        ";
        $conn->query($allocation_query);
    }
    $message = "Subjects allocated successfully!";
}

// Fetch staff members
$staff_query = "SELECT id, first_name, surname FROM users WHERE role = 'staff'";
$staff_result = $conn->query($staff_query);

// Fetch subjects not yet allocated
$subject_query = "
    SELECT id, name AS subject_name, subject_code 
    FROM subjects 
    WHERE id NOT IN (SELECT subject_id FROM subject_allocation)
";
$subject_result = $conn->query($subject_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Allocation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
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

        .content {
            padding: 2em;
            margin: 1em auto;
            max-width: 800px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1em;
        }

        form label {
            font-weight: bold;
        }

        form select {
            padding: 0.5em;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        form button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 0.75em 1em;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .success {
            color: green;
            font-weight: bold;
            margin-bottom: 1em;
        }

        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 1em;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin-panel.php">Back to Admin Panel</a>
    </div>

    <div class="content">
        <h2>Allocate Subjects to Staff</h2>

        <?php if (isset($message)): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>

        <form action="subject-allocation.php" method="POST">
            <label for="staff_id">Select Staff:</label>
            <select name="staff_id" id="staff_id" required>
                <option value="">-- Select Staff --</option>
                <?php while ($staff = $staff_result->fetch_assoc()) { ?>
                    <option value="<?= $staff['id'] ?>">
                        <?= $staff['first_name'] . ' ' . $staff['surname'] ?>
                    </option>
                <?php } ?>
            </select>
            
            <label for="subject_ids">Select Subjects:</label>
            <select name="subject_ids[]" id="subject_ids" multiple required>
                <?php while ($subject = $subject_result->fetch_assoc()) { ?>
                    <option value="<?= $subject['id'] ?>">
                        <?= $subject['subject_code'] . ' - ' . $subject['subject_name'] ?>
                    </option>
                <?php } ?>
            </select>
            <p>Hold Ctrl (or Cmd on Mac) to select multiple subjects.</p>

            <button type="submit">Allocate Subjects</button>
        </form>
    </div>
</body>
</html>
