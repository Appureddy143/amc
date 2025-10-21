<?php
// Start session and include your PDO database connection
session_start();
// 1. Include the PDO database configuration
// This file creates the $conn variable for you.
require_once 'db-config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Get POST data directly.
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // 3. Use PDO prepare and execute
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        // 4. Pass parameters as an array to execute()
        $stmt->execute([$email]);

        // 5. Fetch the user as an associative array
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // 6. Check if a row was returned
        if ($row) {
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Regenerate session ID
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['branch'] = isset($row['branch']) ? $row['branch'] : 'N/A';

                // Redirect based on role
                switch (strtolower(trim($row['role']))) {
                    case 'admin':
                        header("Location: admin-panel.php");
                        exit;
                    case 'hod':
                        header("Location: hod-panel.php");
                        exit;
                    case 'staff':
                        header("Location: staff-panel.php");
                        exit;
                    case 'principal':
                        header("Location: hod-panel.php");
                        exit;
                    default:
                        error_log("Unknown role for user: " . $row['email']);
                        echo "Unknown role.";
                        break;
                }
            } else {
                // Invalid password
                $_SESSION['error'] = 'Invalid password. Please try again.';
                header("Location: login.php");
                exit;
            }
        } else {
            // User not found
            $_SESSION['error'] = 'User not found. Please contact an admin.';
            header("Location: login.php");
            exit;
        }

    } catch (PDOException $e) {
        // Handle any database query errors
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = 'A database error occurred. Please try again later.';
        header("Location: login.php");
        exit;
    }
}

// Close the PDO connection
$conn = null;
?>
