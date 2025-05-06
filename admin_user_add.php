<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "college_exam_portal");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin details
$staff_id = 'S001';
$first_name = 'Admin';
$surname = 'User';
$dob = '2000-01-01';
$address = '123 Admin St, City';
$email = 'admin@example.com';
$password = 'admin123'; // Plain password

// Hash the password using PHP's password_hash function
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert admin user into the database
$query = "INSERT INTO users (staff_id, first_name, surname, dob, address, email, password, role, created_at) 
          VALUES ('$staff_id', '$first_name', '$surname', '$dob', '$address', '$email', '$hashed_password', 'admin', NOW())";

if ($conn->query($query) === TRUE) {
    echo "Admin user added successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
