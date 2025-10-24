<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

// --- (Optional but Recommended) Security Check ---
// Example: Ensure only staff/admin/hod can create papers
$allowed_roles = ['staff', 'admin', 'hod', 'principal']; 
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    // Redirect or show error if not authorized
    die("Unauthorized access."); 
}

// --- Get Data from POST Request ---
// Use null coalescing operator for safety
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? ''); // Assuming content comes from a form field

// --- Get Required Foreign Keys and Other Data ---
// These MUST be provided by the form or session that submits to this script
$staff_id = $_SESSION['user_id'] ?? null; // Get logged-in user's ID from session
$subject_id = isset($_POST['subject_id']) ? filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT) : null; // Get from form
$exam_time = isset($_POST['exam_time']) ? filter_input(INPUT_POST, 'exam_time', FILTER_VALIDATE_INT) : null; // Get from form

// --- Basic Validation ---
if (empty($title) || empty($content) || empty($staff_id) || empty($subject_id) || empty($exam_time)) {
    // Handle error - redirect back with a message or die
    die("Error: Missing required data (Title, Content, Staff ID, Subject ID, Exam Time).");
}

try {
    // --- Prepare and Execute INSERT statement using PDO ---
    $sql = "INSERT INTO question_papers (staff_id, subject_id, title, content, exam_time) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Execute the statement with all required parameters
    if ($stmt->execute([$staff_id, $subject_id, $title, $content, $exam_time])) {
        // --- Success ---
        // Redirect to a success page or back to a relevant dashboard
        header("Location: success.php?message=PaperSaved"); // Example redirect
        exit();
    } else {
        // --- Handle Execution Error ---
        // Log error details if possible
        $errorInfo = $stmt->errorInfo();
        die("Error: Could not save the question paper. Database error: " . ($errorInfo[2] ?? 'Unknown error'));
    }

} catch (PDOException $e) {
    // --- Handle Database Connection or Preparation Errors ---
    die("Database Error: " . $e->getMessage());
}

// Close connection (optional)
$conn = null; 
?>
