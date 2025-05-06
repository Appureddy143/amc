<?php
session_start();
include('db-config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $dob = trim($_POST['dob']); // User's Date of Birth
    $staff_id = trim($_POST['staff_id']); // Staff ID
    $new_password = trim($_POST['new_password']);

    // Check if user exists with given details
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND dob = ? AND staff_id = ?");
    $stmt->bind_param("sss", $email, $dob, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $email);
        $update_stmt->execute();

        echo "<script>alert('Password updated successfully! You can now login.'); window.location.href = 'login.php';</script>";
        exit;
    } else {
        $error = "Details do not match our records!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
/* General Page Styles */
body {
    font-family: 'Arial', sans-serif;
    background: linear-gradient(to bottom, #f0f4f8, #e0e9f0);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.container {
    background: #ffffff;
    padding: 40px 50px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 450px;
    text-align: center;
    background-color: #f9fafb;
}

h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 20px;
    font-weight: 600;
}

input[type="email"],
input[type="date"],
input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 14px 20px;
    margin: 10px 0;
    border-radius: 6px;
    border: 1px solid #ddd;
    font-size: 16px;
    transition: all 0.3s ease;
}

input[type="email"]:focus,
input[type="date"]:focus,
input[type="text"]:focus,
input[type="password"]:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 5px rgba(52, 152, 219, 0.7);
}

button {
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
}

button:hover {
    background-color: #2980b9;
}

button:active {
    background-color: #21618c;
}

p {
    font-size: 14px;
    color: red;
    margin-top: 20px;
}

@media (max-width: 600px) {
    .container {
        padding: 20px;
    }
    h2 {
        font-size: 20px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <input type="date" name="dob" placeholder="Enter Date of Birth" required>
            <input type="text" name="staff_id" placeholder="Enter Staff ID" required>
            <input type="password" name="new_password" placeholder="Enter new password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>
