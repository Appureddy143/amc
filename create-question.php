<?php
session_start();
// Autoloader for PhpSpreadsheet library
require 'vendor/autoload.php';
include('db-config.php'); // Your PDO database connection

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

// --- Security Check: Ensure only authorized roles can access ---
$allowed_roles = ['staff', 'principal', 'admin', 'hod'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    // Redirect to login if the role is not allowed
    header("Location: login.php");
    exit;
}

// Determine the correct dashboard link based on role
$dashboard_link = match ($_SESSION['role']) {
    'admin' => 'admin_dashboard.php',
    'staff' => 'staff_dashboard.php', // Assuming these filenames
    'hod' => 'hod_dashboard.php',     // Assuming these filenames
    'principal' => 'principal_dashboard.php', // Assuming these filenames
    default => 'login.php', // Fallback
};

// Initialize variables
$subjects = [];
$feedback_message = '';

try {
    // --- Fetch subjects for the dropdown menu using PDO ---
    // Use prepare/execute for consistency, although query() is safe here
    $stmt_subjects = $conn->prepare("SELECT id, name FROM subjects ORDER BY name");
    $stmt_subjects->execute();
    $subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    // --- Handle form submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['question_bank']) && $_FILES['question_bank']['error'] === UPLOAD_ERR_OK) {
        
        // Validate required fields
        if (!isset($_POST['subject_id']) || empty($_POST['subject_id']) || !isset($_POST['exam_time']) || empty($_POST['exam_time'])) {
            throw new Exception("Subject and Exam Time are required.");
        }
        
        // --- File Validation ---
        $file_path = $_FILES['question_bank']['tmp_name'];
        $file_name = $_FILES['question_bank']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['xlsx', 'xls'];

        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Invalid file type. Please upload an Excel file (.xlsx or .xls).");
        }

        // --- Get other form data ---
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $exam_time = filter_input(INPUT_POST, 'exam_time', FILTER_VALIDATE_INT);
        
        if (!$subject_id || !$exam_time || $exam_time < 30 || $exam_time > 180) {
             throw new Exception("Invalid Subject ID or Exam Time provided.");
        }

        // --- Load and Read Spreadsheet ---
        try {
            $spreadsheet = IOFactory::load($file_path);
        } catch (ReaderException $e) {
            throw new Exception("Error reading the Excel file. Ensure it's not corrupted and is a valid format.");
        }
        $sheet = $spreadsheet->getActiveSheet();
        $questions_from_file = [];
        $total_marks_available = 0;

        // Read all questions from the Excel file (skipping the header row)
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                // Read formatted value to handle potential formulas or special types
                $cells[] = $cell->getFormattedValue(); 
            }
            
            // Ensure row has Section, Question, Marks & Question is not empty
            if (count($cells) >= 3 && !empty(trim($cells[1]))) { 
                $marks_value = filter_var(trim($cells[2]), FILTER_VALIDATE_INT); // Validate marks are integer
                if ($marks_value !== false && $marks_value > 0) {
                    $marks = $marks_value;
                    $questions_from_file[] = [
                        'section' => trim($cells[0]),
                        'question' => trim($cells[1]),
                        'marks' => $marks,
                    ];
                    $total_marks_available += $marks;
                }
            }
        }
        
        if (empty($questions_from_file)) {
             throw new Exception("The uploaded Excel file contains no valid questions or is in the wrong format (Expected columns: Section, Question, Marks).");
        }
        
        if ($total_marks_available < 50) {
            throw new Exception("The question bank (Total: $total_marks_available marks) does not have enough marks to generate a 50-mark paper.");
        }

        // --- Logic to generate a 50-mark question paper ---
        $selected_questions = [];
        $total_marks_generated = 0;
        shuffle($questions_from_file); // Randomize questions

        // Simple selection strategy: pick questions until 50 marks are reached or exceeded slightly
        foreach ($questions_from_file as $q) {
            if (($total_marks_generated + $q['marks']) <= 50) {
                $selected_questions[] = $q;
                $total_marks_generated += $q['marks'];
                // Optional: break if exactly 50 is reached and desired
                // if ($total_marks_generated == 50) break; 
            }
            // Optional: If you need exactly 50, you might need more complex logic
            // to swap questions if you slightly overshoot. This simple approach prioritizes
            // getting close to 50 without complex backtracking.
        }
        
        // Check if enough marks were selected (might be slightly less than 50 if question marks don't add up perfectly)
         if ($total_marks_generated < 40) { // Adjust threshold as needed
             throw new Exception("Could not generate a paper close enough to 50 marks with the provided questions.");
         }


        // --- Save the generated paper to the database ---
        // Correctly use staff_id from the session
        $staff_id = $_SESSION['user_id']; 
        $title = "QP-" . date('Ymd-His'); // More unique title
        $content = json_encode($selected_questions); // Store questions as JSON

        $sql = "INSERT INTO question_papers (staff_id, subject_id, title, content, exam_time) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$staff_id, $subject_id, $title, $content, $exam_time])) {
             $feedback_message = "<p class='success-message'>✅ Question Paper (Total Marks: $total_marks_generated) generated and saved successfully!</p>";
        } else {
             throw new Exception("Database error: Could not save the question paper.");
        }

    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Handle cases where file upload might have failed before PHP script execution
        if (isset($_FILES['question_bank']) && $_FILES['question_bank']['error'] !== UPLOAD_ERR_OK) {
             throw new Exception("File upload failed. Error code: " . $_FILES['question_bank']['error']);
        }
    }

} catch (PDOException $e) {
    // Catch database-specific errors
    $feedback_message = "<p class='error-message'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    // Catch general errors (file reading, validation, etc.)
    $feedback_message = "<p class='error-message'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Question Bank</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #333; padding: 12px; text-align: right; }
        .navbar a { color: #fff; text-decoration: none; font-size: 16px; margin: 0 15px; }
        .content { width: 90%; max-width: 800px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; }
        form { display: flex; flex-direction: column; gap: 15px; margin-top: 20px; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        label { font-weight: bold; }
        input[type="number"], input[type="file"], select, button { width: 100%; padding: 10px; border-radius: 4px; font-size: 16px; box-sizing: border-box; border: 1px solid #ddd; }
        button { background-color: #007bff; color: white; cursor: pointer; border: none; transition: background-color 0.3s ease; font-weight: bold; }
        button:hover { background-color: #0056b3; }
        a.sample-link { display: block; text-align: center; margin-top: 10px; color: #007bff; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px;}
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px;}
    </style>
</head>
<body>
    <div class="navbar">
        <a href="<?= htmlspecialchars($dashboard_link) ?>">Back to Dashboard</a> 
    </div>

    <div class="content">
        <h2>Automatic Question Paper Generator</h2>
        
        <?php if (!empty($feedback_message)) echo $feedback_message; ?>

        <form action="generate-paper.php" method="POST" enctype="multipart/form-data">
            <label for="subject_id">1. Select Subject:</label>
            <select name="subject_id" id="subject_id" required>
                <option value="" disabled selected>-- Choose a Subject --</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                <?php endforeach; ?>
                 <?php if (empty($subjects)): ?>
                    <option value="" disabled>No subjects found. Please add subjects first.</option>
                <?php endif; ?>
            </select>

            <label for="exam_time">2. Exam Time (minutes):</label>
            <input type="number" id="exam_time" name="exam_time" required min="30" max="180" placeholder="e.g., 90">

            <label for="question_bank">3. Upload Question Bank (Excel File):</label>
            <input type="file" id="question_bank" name="question_bank" accept=".xlsx, .xls" required>
            
            <a href="question_bank_template.xlsx" class="sample-link" download>Download Excel Template (.xlsx)</a>
            
            <button type="submit">Upload & Generate Paper</button>
        </form>
    </div>
</body>
</html>