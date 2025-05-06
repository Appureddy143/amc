<?php
// Start session and database connection
session_start();
$conn = new mysqli("localhost", "root", "", "college_exam_portal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Fetch user details
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Debugging log to show the role value
        error_log("User Role Fetched: " . $row['role']);  // Log the role for debugging

        // Verify password
        if (password_verify($password, $row['password'])) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['branch'] = isset($row['branch']) ? $row['branch'] : 'N/A'; // Set branch if available

            // Debugging logs - Remove or comment out in production
            error_log("User Role: '" . $_SESSION['role'] . "'");
            error_log("User Branch: " . $_SESSION['branch']);
            error_log("Fetched User Data: " . print_r($row, true));
            error_log("Session Variables: " . print_r($_SESSION, true));

            // Redirect based on role (case-insensitive)
            switch (strtolower(trim($row['role']))) {
                case 'admin':
                    header("Location: admin-panel.php");
                    exit;
                case 'HOD': // Updated role to 'Head of Department'
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
            // Log the failed login attempt for debugging purposes
            error_log("Invalid password for user: " . $email);
            $_SESSION['error'] = 'Invalid password. Please try again.';
            header("Location: login.php");
            exit;
        }
    } else {
        // Log the failed login attempt for debugging purposes
        error_log("User not found: " . $email);
        $_SESSION['error'] = 'User not found. Please contact an admin.';
        header("Location: login.php");
        exit;
    }
}

// Close the database connection
$conn->close();
?>
