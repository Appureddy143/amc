<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

// Ensure only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = ""; // To store feedback messages
$message_type = "error"; // Default message type

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim form data
    $usn = isset($_POST['usn']) ? trim($_POST['usn']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $dob = isset($_POST['dob']) ? trim($_POST['dob']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $branch = isset($_POST['branch']) ? trim($_POST['branch']) : ''; // Get branch
    $password_plain = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Basic validation
    if (empty($usn) || empty($name) || empty($email) || empty($dob) || empty($branch) || empty($password_plain)) {
        $message = "All fields except Address are required!";
    } else {
        try {
            // Check if USN already exists using PDO
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE usn = ?");
            $check_stmt->execute([$usn]);
            $usn_exists = $check_stmt->fetchColumn(); // Returns the count (0 or 1)

            if ($usn_exists > 0) {
                $message = "Error: USN '$usn' already exists!";
            } else {
                // Hash the password
                $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

                // Prepare the INSERT statement for PDO
                // Note: Added the 'branch' column
                $insert_sql = "INSERT INTO students (usn, name, email, dob, address, password, branch) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);

                // Execute the statement with an array of values
                if ($stmt->execute([$usn, $name, $email, $dob, $address, $password_hashed, $branch])) {
                    $message = "Student '$name' added successfully!";
                    $message_type = "success";
                } else {
                    // Check for specific errors if needed, otherwise generic message
                    $message = "Error adding student. Please check the data and try again.";
                }
            }
        } catch (PDOException $e) {
            // Catch database errors
            $message = "Database Error: " . $e->getMessage();
            // In production, you might want a more generic error:
            // $message = "A database error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manually Add Student</title>
    <style>
        /* Consistent styling */
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
        input, textarea, select {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
        button[type="submit"] {
            margin-top: 25px;
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
        button[type="submit"]:hover {
            background-color: #218838;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            width: 100%; 
            box-sizing: border-box;
            text-align: center;
            font-size: 16px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        <h2>Manually Add a New Student</h2>

        <?php 
        // Display feedback message if set
        if (!empty($message)) {
            echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>";
        } 
        ?>

        <form action="manual-add-student.php" method="POST">
            <label for="usn">USN:</label>
            <input type="text" id="usn" name="usn" required>
            
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required>
            
            <label for="address">Address (Optional):</label>
            <textarea id="address" name="address"></textarea>

            <label for="branch">Branch:</label>
            <select name="branch" id="branch" required>
                <option value="" disabled selected>-- Select Branch --</option>
                <option value="CSE">CSE</option>
                <option value="ECE">ECE</option>
                <option value="MECH">MECH</option>
                <option value="CIVIL">CIVIL</option>
            </select>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Add Student</button>
        </form>
        
        <a href="add-student.php" class="back-link">Back to Add Student Options</a>
        <a href="admin_dashboard.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</body>
</html>
