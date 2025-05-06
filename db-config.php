<?php
// db-config.php

$host = "localhost"; // Database host
$username = "root"; // Database username (change to your MySQL username)
$password = ""; // Database password (change if needed)
$database = "college_exam_portal"; // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
