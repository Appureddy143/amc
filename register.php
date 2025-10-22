<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

$message = ""; // To store error or success messages
$message_type = "error"; // Default message type

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data directly, prepared statements handle escaping
    $staff_id = trim($_POST['staff_id']);
    $password = $_POST['password']; // Get plain password
    $confirm_password = $_POST['confirm_password'];
    $dob = $_POST['dob'];

    // Basic validation
    if (empty($staff_id) || empty($password) || empty($confirm_password) || empty($dob)) {
        $message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            // Check if user with this staff_id exists
            $stmt_check = $conn->prepare("SELECT id, password FROM users WHERE staff_id = ?");
            $stmt_check->execute([$staff_id]);
            $user = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // User with this staff_id exists
                if (!empty($user['password'])) {
                    // User already has a password set
                    $message = "This user already has a password set. Cannot set it again.";
                } else {
                    // User exists but doesn't have a password, update it
                    // Hash the new password securely
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Prepare and execute the UPDATE statement
                    $stmt_update = $conn->prepare("UPDATE users SET password = ?, dob = ? WHERE staff_id = ?");
                    
                    if ($stmt_update->execute([$hashed_password, $dob, $staff_id])) {
                        $message = "Password set successfully! You can now login.";
                        $message_type = "success"; // Change message type on success
                    } else {
                        $message = "Error updating password. Please try again.";
                    }
                }
            } else {
                // Staff ID doesn't exist in the users table
                $message = "User with this Staff ID does not exist.";
            }

        } catch (PDOException $e) {
            // Handle potential database errors
            $message = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Initial Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px; /* Add padding for smaller screens */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-top: 0; /* Remove default margin */
            margin-bottom: 20px;
        }
        .registration-form {
            width: 100%; /* Full width on small screens */
            max-width: 400px;
            padding: 25px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #555; /* Slightly softer color */
        }
        input[type="text"],
        input[type="password"],
        input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px; /* Slightly larger font */
            box-sizing: border-box; /* Include padding in width */
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold; /* Make button text bold */
            width: 100%;
            transition: background-color 0.3s; /* Smooth hover effect */
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }
        a.button-link { /* Changed class name for clarity */
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            display: block;
            margin-top: 20px;
            text-align: center;
            font-size: 14px; /* Slightly smaller */
        }
        a.button-link:hover {
             text-decoration: underline;
        }
        /* Popup message styling */
        .popup {
            padding: 15px; /* More padding */
            margin-bottom: 20px; /* Space below popup */
            border-radius: 5px;
            width: 100%; 
            box-sizing: border-box;
            text-align: center;
            font-size: 16px;
            display: none; /* Hidden by default */
        }
        .popup.show {
            display: block;
        }
        .popup.success {
            background-color: #d4edda; /* Lighter green */
            color: #155724; /* Darker green text */
            border: 1px solid #c3e6cb;
        }
        .popup.error {
            background-color: #f8d7da; /* Lighter red */
            color: #721c24; /* Darker red text */
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="registration-form">
        <h2>Set Initial Password</h2>
        
        <!-- Popup Message Area -->
        <div id="popup" class="popup"></div> 

        <form action="register.php" method="post">
            <label for="dob">Your Date of Birth:</label>
            <input type="date" id="dob" name="dob" required>

            <label for="staff_id">Your Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" required>

            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Set Password</button>
            
            <a href="login.php" class="button-link">Back To Login</a>
            
        </form>
    </div>

    <script>
        // Use PHP variables to control the popup
        const message = <?php echo json_encode($message); ?>;
        const messageType = <?php echo json_encode($message_type); ?>;
        
        document.addEventListener("DOMContentLoaded", function () {
            if (message) {
                const popup = document.getElementById("popup");
                popup.textContent = message;
                // Add the correct class ('success' or 'error')
                popup.classList.add(messageType); 
                // Make the popup visible
                popup.classList.add("show"); 

                // Optional: Hide after a few seconds
                // setTimeout(() => {
                //     popup.classList.remove("show");
                // }, 5000); // Hide after 5 seconds
            }
        });
    </script>
</body>
</html>