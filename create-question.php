<?php
session_start();
// Autoloader for PhpSpreadsheet library
require 'vendor/autoload.php';
include('db-config.php'); // Your PDO database connection

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- Security Check: Ensure only authorized roles can access ---
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'principal', 'admin', 'hod'])) {
    // Redirect to login if the role is not allowed
    header("Location: login.php");
    exit;
}

// Initialize variables
$subjects = [];
$feedback_message = '';

try {
    // --- Fetch subjects for the dropdown menu using PDO ---
    $stmt = $conn->query("SELECT id, name FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Handle form submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['question_bank']) && $_FILES['question_bank']['error'] === UPLOAD_ERR_OK) {
        if (!isset($_POST['subject_id']) || empty($_POST['subject_id']) || !isset($_POST['exam_time']) || empty($_POST['exam_time'])) {
            throw new Exception("Subject and Exam Time are required.");
        }

        $file_path = $_FILES['question_bank']['tmp_name'];
        $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $exam_time = filter_input(INPUT_POST, 'exam_time', FILTER_VALIDATE_INT);
        
        // Load the uploaded spreadsheet
        $spreadsheet = IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        $questions_from_file = [];
        $total_marks_available = 0;

        // Read all questions from the Excel file (skipping the header row)
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }
            
            // Ensure the row has the required columns (Section, Question, Marks)
            if (count($cells) >= 3 && !empty($cells[1])) {
                $marks = (int)$cells[2];
                $questions_from_file[] = [
                    'section' => trim($cells[0]),
                    'question' => trim($cells[1]),
                    'marks' => $marks,
                ];
                $total_marks_available += $marks;
            }
        }
        
        if (empty($questions_from_file)) {
             throw new Exception("The uploaded Excel file is empty or in the wrong format.");
        }
        
        if ($total_marks_available < 50) {
            throw new Exception("The question bank does not have enough questions to generate a 50-mark paper.");
        }

        // --- Logic to generate a 50-mark question paper ---
        $selected_questions = [];
        $total_marks_generated = 0;
        shuffle($questions_from_file); // Randomize questions

        foreach ($questions_from_file as $q) {
            if (($total_marks_generated + $q['marks']) <= 50) {
                $selected_questions[] = $q;
                $total_marks_generated += $q['marks'];
            }
        }

        // --- Save the generated paper to the database ---
        $staff_id = $_SESSION['user_id'];
        $title = "QP-" . date('Y-m-d');
        $content = json_encode($selected_questions); // Store questions as JSON

        $sql = "INSERT INTO question_papers (staff_id, subject_id, title, content, exam_time) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$staff_id, $subject_id, $title, $content, $exam_time]);

        $feedback_message = "<p class='success-message'>✅ Question Paper (Total Marks: $total_marks_generated) was generated and saved successfully!</p>";
    }

} catch (Exception $e) {
    // Display any errors that occur
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
        input, select, button { width: 100%; padding: 10px; border-radius: 4px; font-size: 16px; box-sizing: border-box; border: 1px solid #ddd; }
        button { background-color: #007bff; color: white; cursor: pointer; border: none; transition: background-color 0.3s ease; }
        button:hover { background-color: #0056b3; }
        a.sample-link { display: block; text-align: center; margin-top: 10px; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; text-align: center; }
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php">Back to Dashboard</a>
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
            </select>

            <label for="exam_time">2. Exam Time (minutes):</label>
            <input type="number" id="exam_time" name="exam_time" required min="30" max="180" placeholder="e.g., 90">

            <label for="question_bank">3. Upload Question Bank (Excel File):</label>
            <input type="file" id="question_bank" name="question_bank" accept=".xlsx, .xls" required>
            
            <a href="question_bank_template.xlsx" class="sample-link" download>Download Excel Template</a>
            
            <button type="submit">Upload & Generate Paper</button>
        </form>
    </div>
</body>
</html>