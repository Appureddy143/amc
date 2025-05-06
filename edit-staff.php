<?php
session_start();
include('db-config.php');

// Check if the user is logged in as admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Check if staff ID is provided
if (!isset($_GET['id'])) {
    header("Location: admin-panel.php");
    exit;
}

$staff_id = $_GET['id'];

// Fetch staff details
$stmt = $conn->prepare("SELECT id, first_name, surname, branch, email FROM users WHERE id = ? AND role = 'staff'");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();

// Handle form submission (Update staff details)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $surname = trim($_POST['surname']);
    $branch = trim($_POST['branch']);
    $email = trim($_POST['email']);

    $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, surname = ?, branch = ?, email = ? WHERE id = ?");
    $update_stmt->bind_param("ssssi", $first_name, $surname, $branch, $email, $staff_id);
    
    if ($update_stmt->execute()) {
        header("Location: admin-panel.php");
        exit;
    } else {
        $error = "Error updating staff details.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
            height: 100vh;
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            text-align: left;
            margin-top: 10px;
            font-weight: bold;
        }

        input, select {
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            margin-top: 15px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        .back-link {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Edit Staff Details</h2>

        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

        <form action="edit-staff.php?id=<?= $staff_id ?>" method="POST">
            <label>First Name:</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($staff['first_name']) ?>" required>

            <label>Surname:</label>
            <input type="text" name="surname" value="<?= htmlspecialchars($staff['surname']) ?>" required>

            <label>Branch:</label>
            <select name="branch" required>
                <option value="CSE" <?= $staff['branch'] == 'CSE' ? 'selected' : ''; ?>>CSE</option>
                <option value="ECE" <?= $staff['branch'] == 'ECE' ? 'selected' : ''; ?>>ECE</option>
                <option value="MECH" <?= $staff['branch'] == 'IT' ? 'selected' : ''; ?>>MECH</option>
                <option value="CIVIL" <?= $staff['branch'] == 'IT' ? 'selected' : ''; ?>>CIVIL</option>
                <!-- Add more branches here as needed -->
            </select>

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" required>

            <button type="submit">Update</button>
        </form>

        <a href="admin-panel.php" class="back-link">Back to Admin Panel</a>
    </div>

</body>
</html>
