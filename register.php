<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "college_exam_portal");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = ""; // To store error or success messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $conn->real_escape_string($_POST['staff_id']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm_password']);
    $dob = $conn->real_escape_string($_POST['dob']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Check if user with this staff_id already exists, but doesn't have a password
        $query = "SELECT staff_id, password FROM users WHERE staff_id = '$staff_id'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            // User with this staff_id exists
            $user = $result->fetch_assoc();

            if (!empty($user['password'])) {
                // User already has a password set
                $message = "This user already has a password. Cannot assign password again.";
            } else {
                // User exists but doesn't have a password, assign the new password
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Update the password for this user
                $update_query = "UPDATE users SET password = ?, dob = ? WHERE staff_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sss", $hashed_password, $dob, $staff_id);

                if ($stmt->execute()) {
                    $message = "Password set successfully for this user.";
                } else {
                    $message = "Error updating password: " . $conn->error;
                }
                $stmt->close();
            }
        } else {
            // Staff ID doesn't exist, show error
            $message = "User with this Staff ID does not exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password</title>
    <style>
        /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
}

h2 {
    text-align: center;
    color: #333;
}

/* Registration Form Container */
.registration-form {
    max-width: 400px;
    margin: 50px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Form Elements */
label {
    font-weight: bold;
    margin-bottom: 5px;
    display: block;
    color: #333;
}

input[type="text"],
input[type="password"],
input[type="date"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

button {
    background-color: #007bff;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
}

button:hover {
    background-color: #0056b3;
}

a.button {
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
    display: block;
    margin-top: 20px;
    text-align: center;
}

/* Popup message */
#popup {
    display: none;
    padding: 10px;
    margin: 20px auto;
    text-align: center;
    border-radius: 5px;
    width: 80%;
    max-width: 400px;
    position: fixed;
    top: 10%;
    left: 50%;
    transform: translateX(-50%);
    font-size: 16px;
    z-index: 1000;
}

#popup.show {
    display: block;
}

#popup.success {
    background-color: #28a745;
    color: white;
}

#popup.error {
    background-color: #dc3545;
    color: white;
}

    </style>
</head>
<body>
    <div class="registration-form">
        <h2>Set Password</h2>
        <form action="register.php" method="post">
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required>

            <label for="staff_id">Staff ID:</label>
            <input type="text" id="staff_id" name="staff_id" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Set Password</button>
            <div>
                <a href="login.php" class="button">Back To Login</a>
            </div>
        </form>
    </div>

    <!-- Popup Message -->
    <div id="popup" class="popup"></div>

    <script>
        // Display popup message
        document.addEventListener("DOMContentLoaded", function () {
            const message = "<?php echo $message; ?>";
            if (message) {
                const popup = document.getElementById("popup");
                popup.textContent = message;
                popup.classList.add("show");
                popup.classList.add(message.includes("successful") ? "success" : "error");

                // Hide the popup after 3 seconds
                setTimeout(() => {
                    popup.classList.remove("show");
                }, 3000);
            }
        });
    </script>
</body>
</html>
