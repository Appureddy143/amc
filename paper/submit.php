<?php
$servername = "localhost";
$username = "root"; // Change as needed
$password = ""; // Change as needed
$dbname = "college_exam_portal"; // Change as needed

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO question_papers (title, content) VALUES (?, ?)");
$stmt->bind_param("ss", $title, $content);

// Set parameters and execute
$title = $_POST['title'];
$content = $_POST['content'];
$stmt->execute();

$stmt->close();
$conn->close();

// Redirect or show success message
header("Location: success.php");
exit();
?>