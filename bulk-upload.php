<?php
session_start();
include('db-config.php'); // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $first_name = $data[0];
        $surname = $data[1];
        $dob = $data[2];
        $address = $data[3];
        $staff_id = $data[4];
        $email = $data[5];
        $role = $data[6];

        // Insert into database
        $query = "INSERT INTO users (staff_id, first_name, surname, dob, address, email, password, role, created_at) 
                  VALUES ('$staff_id', '$first_name', '$surname', '$dob', '$address', '$email', PASSWORD('password123'), '$role', NOW())";
        
        $conn->query($query);
    }

    fclose($handle);
    echo "CSV file successfully uploaded!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload</title>
</head>
<body>
    <h2>Upload Staff or Student CSV</h2>
    <form action="bulk-upload.php" method="POST" enctype="multipart/form-data">
        <label>Select CSV File:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit">Upload CSV</button>
    </form>
</body>
</html>
