<?php
session_start();
include('db-config.php'); // Include database connection

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data safely
    $staff_id = trim($_POST['staff_id']);
    $first_name = trim($_POST['first_name']);
    $surname = trim($_POST['surname']);
    $dob = $_POST['dob'];
    $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : NULL;
    $address = trim($_POST['address']);
    $branch = trim($_POST['branch']);
    $role = strtolower(trim($_POST['role'])); // Convert to lowercase
    $email = trim($_POST['email']);

    // Allowed roles validation (Updated to 'DeptHead' instead of 'department head')
    $allowed_roles = ["staff", "HOD", "principal"];
    if (!in_array($role, $allowed_roles)) {
        die("<p class='error-message'>❌ Error: Unknown Role. Please select a valid role.</p>");
    }

    // Debugging: Output the role to check it's being received
    // You can comment this out after verifying it's working
    echo "<p>Role selected: $role</p>";

    // Check if staff_id already exists
    $check_stmt = $conn->prepare("SELECT staff_id FROM users WHERE staff_id = ?");
    $check_stmt->bind_param("i", $staff_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<p class='error-message'>❌ Error: Staff ID already exists. Please use a different ID.</p>";
        $check_stmt->close();
    } else {
        $check_stmt->close();

        // File upload handling
        $upload_dir = "uploads/";
        $upload_errors = [];

        // Validate and upload Photo (JPEG only)
        $photo_path = $upload_dir . basename($_FILES['photo']['name']);
        if ($_FILES['photo']['type'] !== 'image/jpeg') {
            $upload_errors[] = "Photo must be a JPEG file.";
        } elseif (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $upload_errors[] = "Error uploading photo.";
        }

        // Validate and upload Marks Card (PDF only)
        $marks_card_path = $upload_dir . basename($_FILES['marks_card']['name']);
        if ($_FILES['marks_card']['type'] !== 'application/pdf') {
            $upload_errors[] = "Marks card must be a PDF file.";
        } elseif (!move_uploaded_file($_FILES['marks_card']['tmp_name'], $marks_card_path)) {
            $upload_errors[] = "Error uploading marks card.";
        }

        // Validate and upload Experience Letter (PDF only)
        $experience_letter_path = $upload_dir . basename($_FILES['experience_letter']['name']);
        if ($_FILES['experience_letter']['type'] !== 'application/pdf') {
            $upload_errors[] = "Experience letter must be a PDF file.";
        } elseif (!move_uploaded_file($_FILES['experience_letter']['tmp_name'], $experience_letter_path)) {
            $upload_errors[] = "Error uploading experience letter.";
        }

        // If there are file upload errors, display them
        if (!empty($upload_errors)) {
            foreach ($upload_errors as $error) {
                echo "<p class='error-message'>$error</p>";
            }
        } else {
            // Insert staff details into the database using prepared statements
            $stmt = $conn->prepare("INSERT INTO users (staff_id, first_name, surname, dob, address, photo_path, marks_card_path, experience_letter_path, email, role, branch, joining_date, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            // Bind parameters (12 total, no password)
            $stmt->bind_param("isssssssssss", $staff_id, $first_name, $surname, $dob, $address, 
                              $photo_path, $marks_card_path, $experience_letter_path, $email, 
                              $role, $branch, $joining_date);

            // Execute the query
            if ($stmt->execute()) {
                echo "<p class='success-message'>✅ New staff member added successfully.</p>";
            } else {
                echo "<p class='error-message'>❌ Error: " . $stmt->error . "</p>";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff</title>
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .content {
            max-width: 700px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,  0, 0, 0.1);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        input,
        select,
        button {
            font-size: 14px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        button {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            padding: 12px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            font-size: 14px;
        }

        .success-message {
            color: green;
            font-size: 14px;
        }

        /* Back to Dashboard Button */
        .back-to-dashboard {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background-color:rgb(3, 112, 255);
            color: white;
            font-weight: bold;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            width: 94%;
        }

        .back-to-dashboard:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Add New Staff</h2>
        <form action="add-staff.php" method="POST" enctype="multipart/form-data">
            <label>First Name:</label> <input type="text" name="first_name" required>
            <label>Surname:</label> <input type="text" name="surname" required>
            <label>DOB:</label> <input type="date" name="dob" required>
            <label>Joining Date:</label> <input type="date" name="joining_date" required>
            <label>Address:</label> <input type="text" name="address" required>
            <label>Staff ID:</label> <input type="number" name="staff_id" required>
            <label>Branch:</label> <select name="branch" required><option value="CSE">CSE</option><option value="ECE">ECE</option><option value="MECH">MECH</option><option value="CIVIL">CIVIL</option></select>
            <label>Role:</label> <select name="role" required><option value="staff">Staff</option><option value="HOD">HOD</option><option value="principal">Principal</option></select>
            <label>Email:</label> <input type="email" name="email" required>
            <label>Photo (JPEG only):</label> <input type="file" name="photo" accept="image/jpeg" required>
            <label>Marks Card (PDF only):</label> <input type="file" name="marks_card" accept="application/pdf" required>
            <label>Experience Letter (PDF only):</label> <input type="file" name="experience_letter" accept="application/pdf" required>
            <button type="submit">Add Staff</button>
        </form>
        
        <!-- Back to Dashboard Button -->
        <a href="admin-panel.php" class="back-to-dashboard">Back to Dashboard</a>
    </div>
</body>
</html>
