<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "college_exam_portal");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User data
$staff_id = 'S001';
$first_name = 'Admin';
$surname = 'User';
$dob = '2009-01-01';
$address = '123 Admin St, City';
$photo_path = 'pic/admin.gif';
$marks_card_path = 'path/to/marks_card.jpg';
$experience_letter_path = 'path/to/experience_letter.jpg';
$email = 'admin@example.com';
$password = 'admin123'; // This is the password you want to hash

// Hash the password using PHP's password_hash function
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// SQL query to insert the user into the database
$query = "INSERT INTO users (staff_id, first_name, surname, dob, address, photo_path, marks_card_path, experience_letter_path, email, password, role, subject_code, branch, created_at) 
          VALUES ('$staff_id', '$first_name', '$surname', '$dob', '$address', '$photo_path', '$marks_card_path', '$experience_letter_path', '$email', '$hashed_password', 'admin', NULL, NULL, NOW())";

// Execute the query
if ($conn->query($query) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $query . "<br>" . $conn->error;
}

// Close the database connection
$conn->close();
?>



