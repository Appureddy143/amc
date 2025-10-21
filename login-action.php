<?php
// Start session and include your PDO database connection
session_start();
require_once 'db-config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get POST data and trim whitespace from the input password
    $email = $_POST['email'];
    $input_password = trim($_POST['password']);

    try {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Get the stored hash and trim any whitespace from it
            $stored_hash = trim($row['password']);

            // --- TEMPORARY DEBUGGING ---
            // This will write the exact values to your Render logs
            error_log("Attempting login for: " . $email);
            error_log("Input Password: '" . $input_password . "'");
            error_log("Stored Hash:    '" . $stored_hash . "'");
            // --- END DEBUGGING ---

            // Verify password
            if (password_verify($input_password, $stored_hash)) {
                
                // --- LOGIN SUCCESS ---
                error_log("Password VERIFIED for: " . $email);
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['branch'] = isset($row['branch']) ? $row['branch'] : 'N/A';

                switch (strtolower(trim($row['role']))) {
                    case 'admin':
                        header("Location: admin_dashboard.php"); // Corrected filename
                        exit;
                    // ... other cases
                    default:
                         header("Location: admin_dashboard.php"); // Fallback
                         exit;
                }

            } else {
                // --- LOGIN FAIL ---
                error_log("Password FAILED for: " . $email);
                $_SESSION['error'] = 'Invalid password. Please try again.';
                header("Location: login.php");
                exit;
            }
        } else {
            error_log("User not found: " . $email);
            $_SESSION['error'] = 'User not found. Please contact an admin.';
            header("Location: login.php");
            exit;
        }

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = 'A database error occurred. Please try again later.';
        header("Location: login.php");
        exit;
    }
}

$conn = null;
?>