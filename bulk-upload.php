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

try {
    // --- Handle STAFF CSV Upload ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_staff']) && isset($_FILES['staff_csv']) && $_FILES['staff_csv']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['staff_csv']['tmp_name'];
        if (($handle = fopen($file, 'r')) !== FALSE) {
            $conn->beginTransaction();
            
            $sql = "INSERT INTO users (staff_id, first_name, surname, dob, joining_date, address, branch, role, email, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            fgetcsv($handle); // Skip header row

            while (($data = fgetcsv($handle)) !== FALSE) {
                $password = password_hash($data[9], PASSWORD_DEFAULT); // Hash the password from the CSV
                $stmt->execute([$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $password]);
            }
            
            $conn->commit();
            fclose($handle);
            $feedback_message = "<p class='success-message'>✅ Staff CSV file successfully uploaded!</p>";
        }
    }

    // --- Handle STUDENT CSV Upload ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_students']) && isset($_FILES['student_csv']) && $_FILES['student_csv']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['student_csv']['tmp_name'];
        if (($handle = fopen($file, 'r')) !== FALSE) {
            $conn->beginTransaction();

            $sql = "INSERT INTO students (usn, name, email, dob, address, password) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            fgetcsv($handle); // Skip header row

            while (($data = fgetcsv($handle)) !== FALSE) {
                $password = password_hash($data[5], PASSWORD_DEFAULT); // Hash the password from the CSV
                $stmt->execute([$data[0], $data[1], $data[2], $data[3], $data[4], $password]);
            }

            $conn->commit();
            fclose($handle);
            $feedback_message = "<p class='success-message'>✅ Student CSV file successfully uploaded!</p>";
        }
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $feedback_message = "<p class='error-message'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    $feedback_message = "<p class='error-message'>❌ An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Data</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #333; padding: 12px; text-align: right; }
        .navbar a { color: #fff; text-decoration: none; font-size: 16px; margin: 0 15px; }
        .content { width: 90%; max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2, h3 { text-align: center; }
        form { display: flex; flex-direction: column; align-items: center; gap: 10px; margin-top: 20px; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        input[type="file"], button { width: 100%; max-width: 400px; padding: 10px; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        input[type="file"] { border: 1px solid #ddd; }
        button { background-color: #007bff; color: white; cursor: pointer; border: none; transition: background-color 0.3s ease; }
        button:hover { background-color: #0056b3; }
        a.sample-link { display: block; text-align: center; margin-top: 10px; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </div>

    <div class="content">
        <h2>Bulk Upload Data</h2>
        <?php if (!empty($feedback_message)) echo $feedback_message; ?>

        <hr>

        <h3>Upload Staff CSV</h3>
        <form action="bulk-upload.php" method="POST" enctype="multipart/form-data">
            <label for="staff_csv">Select Staff CSV File:</label>
            <input type="file" id="staff_csv" name="staff_csv" accept=".csv" required>
            <button type="submit" name="upload_staff">Upload Staff</button>
            <a href="sample_staff.csv" class="sample-link" download>Download Staff Sample CSV</a>
        </form>

        <hr style="margin: 30px 0;">

        <h3>Upload Student CSV</h3>
        <form action="bulk-upload.php" method="POST" enctype="multipart/form-data">
            <label for="student_csv">Select Student CSV File:</label>
            <input type="file" id="student_csv" name="student_csv" accept=".csv" required>
            <button type="submit" name="upload_students">Upload Students</button>
            <a href="sample_students.csv" class="sample-link" download>Download Student Sample CSV</a>
        </form>
    </div>
</body>
</html>