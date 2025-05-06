<?php
session_start();
include('db-config.php');

// Check if the user is logged in as admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['student_csv'])) {
    $fileName = $_FILES['student_csv']['tmp_name'];

    if ($_FILES['student_csv']['size'] > 0) {
        $file = fopen($fileName, "r");
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $usn = $conn->real_escape_string($data[0]); // USN
            $name = $conn->real_escape_string($data[1]);
            $email = $conn->real_escape_string($data[2]);
            $dob = $conn->real_escape_string($data[3]);
            $address = $conn->real_escape_string($data[4]);
            $branch = $conn->real_escape_string($data[5]);  // Branch
            $password = password_hash($data[6], PASSWORD_DEFAULT);

            // Insert student into the database
            $query = "INSERT INTO users (usn, first_name, email, dob, address, password, branch, role) 
                      VALUES ('$usn', '$name', '$email', '$dob', '$address', '$password', '$branch', 'student')";
            $conn->query($query);
        }
        fclose($file);
        $message = "Students added successfully!";
    } else {
        $message = "Please upload a valid CSV file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        .navbar {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            font-size: 18px;
        }

        .navbar a:hover {
            color: #f1f1f1;
        }

        /* Content Section */
        .content {
            width: 85%; /* Increased width from 80% to 85% */
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #333;
            text-align: center;
        }

        h3 {
            color: #555;
            margin-top: 20px;
        }

        .message {
            color: green;
            font-weight: bold;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input, textarea, select, button {
            width: 80%;
            max-width: 400px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        input[type="file"] {
            width: 80%;
            max-width: 400px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        button[type="button"] {
            background-color: #007bff;
            border: none;
        }

        button[type="button"]:hover {
            background-color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .content {
                width: 90%;
            }

            input, textarea, button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin-panel.php">Back to Admin Panel</a>
    </div>
    <div class="content">
        <h2>Add Student</h2>
        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

        <h3>Upload CSV for Bulk Registration</h3>
        <form action="add-student.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="student_csv" accept=".csv" required>
            <button type="submit" class="btn">Upload</button>
            <button type="button" class="btn" onclick="downloadFile()">Sample CSV</button>
        </form>

        <h3>Manually Add a Student</h3>
        <form action="manual-add-student.php" method="POST">
            <input type="text" name="usn" placeholder="USN" required>
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="date" name="dob" placeholder="Date of Birth" required>
            <textarea name="address" placeholder="Address"></textarea>

            <!-- Branch Selection -->
            <select name="branch" required>
            <option value="CSE">CSE</option>
                <option value="ECE">ECE</option>
                <option value="MECH">MECH</option>
                <option value="CIVIL">CIVIL</option>
                <!-- Add more branches as required -->
            </select>

            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Add Student</button>
        </form>
    </div>

    <script>
        function downloadFile() {
            // Specify the file URL
            const fileUrl = 'sample.csv';  // Replace with the actual file path

            // Create an anchor element
            const a = document.createElement('a');
            a.href = fileUrl;
            a.download = 'sample.csv';  // Set the file name

            // Trigger the download
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
