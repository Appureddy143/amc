<?php
session_start();
include('db-config.php'); // Include your PDO database connection

// Check if the user is logged in as an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Initialize a variable to hold feedback messages
$feedback_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data safely
        $staff_id = trim($_POST['staff_id']);
        $first_name = trim($_POST['first_name']);
        $surname = trim($_POST['surname']);
        $dob = $_POST['dob'];
        $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : NULL;
        $address = trim($_POST['address']);
        $branch = trim($_POST['branch']);
        $role = trim($_POST['role']);
        $email = trim($_POST['email']);

        // --- 1. Role Validation ---
        $allowed_roles = ["staff", "HOD", "principal"];
        if (!in_array($role, $allowed_roles)) {
            throw new Exception("Error: Unknown Role. Please select a valid role.");
        }

        // --- 2. Check if staff_id already exists using PDO ---
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE staff_id = ?");
        $check_stmt->execute([$staff_id]);
        if ($check_stmt->fetch()) {
            throw new Exception("Error: Staff ID already exists. Please use a different ID.");
        }

        // --- 3. File Upload Handling (No database changes needed here) ---
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
        }
        $upload_errors = [];

        // Validate and upload Photo
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['photo']['type'] !== 'image/jpeg') {
                $upload_errors[] = "Photo must be a JPEG file.";
            } else {
                $photo_filename = uniqid('photo_') . '.jpg';
                $photo_path = $upload_dir . $photo_filename;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $upload_errors[] = "Error uploading photo.";
                    $photo_path = null;
                }
            }
        }

        // Validate and upload Marks Card
        $marks_card_path = null;
        if (isset($_FILES['marks_card']) && $_FILES['marks_card']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['marks_card']['type'] !== 'application/pdf') {
                $upload_errors[] = "Marks card must be a PDF file.";
            } else {
                $marks_card_filename = uniqid('marks_') . '.pdf';
                $marks_card_path = $upload_dir . $marks_card_filename;
                if (!move_uploaded_file($_FILES['marks_card']['tmp_name'], $marks_card_path)) {
                    $upload_errors[] = "Error uploading marks card.";
                    $marks_card_path = null;
                }
            }
        }
        
        // Validate and upload Experience Letter
        $experience_letter_path = null;
        if (isset($_FILES['experience_letter']) && $_FILES['experience_letter']['error'] === UPLOAD_ERR_OK) {
             if ($_FILES['experience_letter']['type'] !== 'application/pdf') {
                $upload_errors[] = "Experience letter must be a PDF file.";
            } else {
                $exp_letter_filename = uniqid('exp_') . '.pdf';
                $experience_letter_path = $upload_dir . $exp_letter_filename;
                if (!move_uploaded_file($_FILES['experience_letter']['tmp_name'], $experience_letter_path)) {
                    $upload_errors[] = "Error uploading experience letter.";
                    $experience_letter_path = null;
                }
            }
        }

        if (!empty($upload_errors)) {
            throw new Exception(implode("<br>", $upload_errors));
        }

        // --- 4. Insert staff details into the database using PDO ---
        // Note: Password is not set here. This user cannot log in until a password is created for them.
        $sql = "INSERT INTO users 
                    (staff_id, first_name, surname, dob, address, photo_path, marks_card_path, experience_letter_path, email, role, branch, joining_date, password, created_at) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);

        // Generate a temporary, unusable password hash
        $temp_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        
        $stmt->execute([
            $staff_id, $first_name, $surname, $dob, $address, 
            $photo_path, $marks_card_path, $experience_letter_path, 
            $email, $role, $branch, $joining_date, $temp_password
        ]);

        $feedback_message = "<p class='success-message'>✅ New staff member added successfully.</p>";

    } catch (PDOException $e) {
        $feedback_message = "<p class='error-message'>❌ Database Error: " . $e->getMessage() . "</p>";
    } catch (Exception $e) {
        $feedback_message = "<p class='error-message'>❌ " . $e->getMessage() . "</p>";
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
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; color: #333; }
        .content { max-width: 700px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: bold; }
        input, select, button { font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
        button { background-color: #007bff; color: white; font-weight: bold; cursor: pointer; border: none; padding: 12px; }
        button:hover { background-color: #0056b3; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .back-to-dashboard { display: block; margin-top: 20px; padding: 12px; background-color: #28a745; color: white; font-weight: bold; text-decoration: none; border-radius: 4px; text-align: center; }
        .back-to-dashboard:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="content">
        <h2>Add New Staff Member</h2>

        <!-- Display feedback message here -->
        <?php if (!empty($feedback_message)) echo $feedback_message; ?>

        <form action="add-staff.php" method="POST" enctype="multipart/form-data">
            <label>First Name:</label> <input type="text" name="first_name" required>
            <label>Surname:</label> <input type="text" name="surname" required>
            <label>Date of Birth:</label> <input type="date" name="dob" required>
            <label>Joining Date:</label> <input type="date" name="joining_date">
            <label>Address:</label> <input type="text" name="address" required>
            <label>Staff ID:</label> <input type="text" name="staff_id" required>
            <label>Branch:</label> 
            <select name="branch" required>
                <option value="CSE">CSE</option>
                <option value="ECE">ECE</option>
                <option value="MECH">MECH</option>
                <option value="CIVIL">CIVIL</option>
            </select>
            <label>Role:</label> 
            <select name="role" required>
                <option value="staff">Staff</option>
                <option value="HOD">HOD</option>
                <option value="principal">Principal</option>
            </select>
            <label>Email:</label> <input type="email" name="email" required>
            <label>Photo (JPEG only):</label> <input type="file" name="photo" accept="image/jpeg" required>
            <label>Marks Card (PDF only):</label> <input type="file" name="marks_card" accept="application/pdf" required>
            <label>Experience Letter (PDF only):</label> <input type="file" name="experience_letter" accept="application/pdf" required>
            <button type="submit">Add Staff</button>
        </form>

        <a href="admin_dashboard.php" class="back-to-dashboard">Back to Dashboard</a>
    </div>
</body>
</html>