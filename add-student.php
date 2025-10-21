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
    // --- Handle CSV Upload ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['student_csv']) && $_FILES['student_csv']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['student_csv']['tmp_name'];

        if ($_FILES['student_csv']['size'] > 0) {
            $file = fopen($fileName, "r");
            
            $conn->beginTransaction();
            
            // Prepare the statement once before the loop
            $sql = "INSERT INTO students (usn, name, email, dob, address, password) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Skip the header row
            fgetcsv($file, 1000, ",");

            while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
                // CSV columns: usn, name, email, dob, address, branch, password
                $usn = $data[0];
                $name = $data[1];
                $email = $data[2];
                $dob = $data[3];
                $address = $data[4];
                // We are skipping branch ($data[5]) as it's not in the students table
                $password = password_hash($data[6], PASSWORD_DEFAULT);

                $stmt->execute([$usn, $name, $email, $dob, $address, $password]);
            }
            
            $conn->commit();
            fclose($file);
            $feedback_message = "<p class='success-message'>✅ Students from CSV uploaded successfully!</p>";
        } else {
            throw new Exception("Please upload a valid CSV file.");
        }
    }

    // --- Handle Manual Student Entry ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['manual_add_student'])) {
        $usn = trim($_POST['usn']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $dob = $_POST['dob'];
        $address = trim($_POST['address']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO students (usn, name, email, dob, address, password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$usn, $name, $email, $dob, $address, $password]);

        $feedback_message = "<p class='success-message'>✅ Student added successfully!</p>";
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $feedback_message = "<p class='error-message'>❌ Database Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    $feedback_message = "<p class='error-message'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #333; padding: 12px; text-align: right; }
        .navbar a { color: #fff; text-decoration: none; font-size: 16px; margin: 0 15px; }
        .content { width: 90%; max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2, h3 { text-align: center; }
        form { display: flex; flex-direction: column; align-items: center; gap: 10px; margin-top: 20px; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        input, textarea, select, button { width: 100%; max-width: 400px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
        textarea { height: 100px; resize: vertical; }
        button { background-color: #28a745; color: white; cursor: pointer; border: none; transition: background-color 0.3s ease; }
        button:hover { background-color: #218838; }
        .csv-form button[type="button"] { background-color: #007bff; }
        .csv-form button[type="button"]:hover { background-color: #0056b3; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_panel.php">Back to Admin Dashboard</a>
    </div>
    <div class="content">
        <h2>Add Students</h2>

        <?php if (!empty($feedback_message)) echo $feedback_message; ?>

        <h3>Upload CSV for Bulk Registration</h3>
        <form class="csv-form" action="add-student.php" method="POST" enctype="multipart/form-data">
            <p>CSV format: usn, name, email, dob, address, branch, password</p>
            <input type="file" name="student_csv" accept=".csv" required>
            <button type="submit">Upload CSV</button>
            <button type="button" onclick="downloadFile()">Download Sample CSV</button>
        </form>

        <h3>Or Manually Add a Single Student</h3>
        <form action="add-student.php" method="POST">
            <input type="hidden" name="manual_add_student" value="1">
            <input type="text" name="usn" placeholder="USN" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="date" name="dob" placeholder="Date of Birth" required>
            <textarea name="address" placeholder="Address"></textarea>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Add Student</button>
        </form>
    </div>

    <script>
        function downloadFile() {
            // Create a link and trigger a click to download the sample file
            const a = document.createElement('a');
            a.href = 'sample.csv';
            a.download = 'sample.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>