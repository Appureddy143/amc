<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

$message = ""; // To store feedback messages
$message_type = "error"; // Default message type

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $dob = isset($_POST['dob']) ? trim($_POST['dob']) : ''; // User's Date of Birth
    $staff_id = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : ''; // Staff ID
    $new_password_plain = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    // Basic validation
    if (empty($email) || empty($dob) || empty($staff_id) || empty($new_password_plain)) {
        $message = "All fields are required.";
    } else {
        try {
            // Check if user exists with the given details using PDO
            $check_sql = "SELECT id FROM users WHERE email = ? AND dob = ? AND staff_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->execute([$email, $dob, $staff_id]);
            $user = $check_stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user record

            if ($user) {
                // User found, proceed to update password
                // Hash the new password securely
                $hashed_password = password_hash($new_password_plain, PASSWORD_DEFAULT); // Use PASSWORD_DEFAULT

                // Prepare and execute the UPDATE statement using PDO
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                
                if ($update_stmt->execute([$hashed_password, $email])) {
                    $message = "Password updated successfully! You can now login.";
                    $message_type = "success";
                    // Optionally redirect after a short delay or just show message
                    // header("refresh:3;url=login.php"); // Example redirect after 3 seconds
                } else {
                    $message = "Error updating password. Please try again.";
                }
            } else {
                // Details did not match any user record
                $message = "Details do not match our records!";
            }
        } catch (PDOException $e) {
            // Handle potential database errors
            $message = "Database Error: " . $e->getMessage();
            // In production, use a generic error:
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
    <title>Forgot Password</title>
    <style>
        /* Consistent Styling */
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #f0f4f8, #e0e9f0);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-sizing: border-box;
        }
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 25px;
            font-weight: 600;
        }
        label { /* Added labels for accessibility */
             display: block;
             text-align: left;
             margin-bottom: 5px;
             font-weight: bold;
             color: #555;
        }
        input[type="email"],
        input[type="date"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px; /* Adjusted padding */
            margin-bottom: 15px; /* Consistent margin */
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5); /* Softer focus */
        }
        button[type="submit"] {
            width: 100%;
            padding: 14px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
            margin-top: 10px; /* Added margin */
        }
        button:hover {
            background-color: #2980b9;
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
         .login-link { /* Style for the login link */
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
            font-size: 14px;
        }
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        
        <?php 
        // Display feedback message if set
        if (!empty($message)) {
            echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>";
        } 
        ?>
        
        <form action="forgot-password.php" method="POST">
             <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
            
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required>
            
            <label for="staff_id">Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" placeholder="Enter Staff ID" required>
            
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
            
            <button type="submit">Reset Password</button>
        </form>
        
        <a href="login.php" class="login-link">Back to Login</a>
    </div>
</body>
</html>
