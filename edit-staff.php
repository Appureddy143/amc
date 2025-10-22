<?php
session_start();
include('db-config.php'); // Your PDO database connection

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if staff ID is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php"); // Redirect to correct dashboard
    exit;
}

$staff_id = (int)$_GET['id'];
$error = '';
$staff = null;

try {
    // --- Handle form submission (Update staff details) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $first_name = trim($_POST['first_name']);
        $surname = trim($_POST['surname']);
        $branch = trim($_POST['branch']);
        $email = trim($_POST['email']);

        // Prepare and execute the UPDATE statement
        $update_sql = "UPDATE users SET first_name = ?, surname = ?, branch = ?, email = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt->execute([$first_name, $surname, $branch, $email, $staff_id])) {
            // Redirect to the main dashboard on success
            header("Location: admin_dashboard.php?status=updated");
            exit;
        } else {
            $error = "Error updating staff details. Please try again.";
        }
    }

    // --- Fetch staff details to pre-fill the form ---
    $stmt = $conn->prepare("SELECT id, first_name, surname, branch, email, role FROM users WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no staff member is found with that ID, redirect
    if (!$staff) {
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
    <title>Edit Staff</title>
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
        <h2>Edit Staff Details</h2>

        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        
        <?php if ($staff): // Only show the form if staff details were successfully fetched ?>
            <form action="edit-staff.php?id=<?= $staff_id ?>" method="POST">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($staff['first_name']) ?>" required>

                <label for="surname">Surname:</label>
                <input type="text" id="surname" name="surname" value="<?= htmlspecialchars($staff['surname']) ?>" required>

                <label for="branch">Branch:</label>
                <select name="branch" id="branch" required>
                    <!-- Corrected the selection logic for each option -->
                    <option value="CSE" <?= ($staff['branch'] ?? '') == 'CSE' ? 'selected' : ''; ?>>CSE</option>
                    <option value="ECE" <?= ($staff['branch'] ?? '') == 'ECE' ? 'selected' : ''; ?>>ECE</option>
                    <option value="MECH" <?= ($staff['branch'] ?? '') == 'MECH' ? 'selected' : ''; ?>>MECH</option>
                    <option value="CIVIL" <?= ($staff['branch'] ?? '') == 'CIVIL' ? 'selected' : ''; ?>>CIVIL</option>
                </select>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required>

                <button type="submit">Update Details</button>
            </form>
        <?php else: ?>
            <p>Could not find user details.</p>
        <?php endif; ?>

        <a href="admin-panel.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</body>
</html>