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
    header("Location: login.php");
    exit;
}

// Determine the correct dashboard link based on role
$dashboard_link = match ($_SESSION['role']) {
    'admin' => 'admin_dashboard.php',
    'staff' => 'staff_dashboard.php',
    'hod' => 'hod_dashboard.php',
    'principal' => 'principal_dashboard.php',
    default => 'login.php', // Fallback
};

// Initialize variables
$subjects = [];
$feedback_message = '';
$message_type = 'error'; // Default message type

try {
    // --- Fetch subjects for the dropdown menu using PDO ---
    $stmt_subjects = $conn->prepare("SELECT id, name FROM subjects ORDER BY name");
    $stmt_subjects->execute();
    $subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    // --- Handle form submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['question_bank']) && $_FILES['question_bank']['error'] === UPLOAD_ERR_OK) {

        // Validate required fields
        if (!isset($_POST['subject_id']) || empty($_POST['subject_id']) ||
            !isset($_POST['exam_time']) || empty($_POST['exam_time']) ||
            !isset($_POST['question_type']) || empty($_POST['question_type'])) {
            throw new Exception("Subject, Exam Time, and Question Type are required.");
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
        $question_type = trim($_POST['question_type']); // 'mcq' or 'descriptive'

        if (!$subject_id || !$exam_time || $exam_time < 30 || $exam_time > 180 || !in_array($question_type, ['mcq', 'descriptive'])) {
             throw new Exception("Invalid Subject ID, Exam Time, or Question Type provided.");
        }

        // --- Load and Read Spreadsheet ---
        try {
            $spreadsheet = IOFactory::load($file_path);
        } catch (ReaderException $e) {
            throw new Exception("Error reading the Excel file. Ensure it's not corrupted and is a valid format.");
        }
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Basic check for empty or header-only file
        if ($highestRow < 2) {
             throw new Exception("The uploaded Excel file appears to be empty or only contains a header row.");
        }

        $questions_from_file = [];
        $total_marks_available = 0;

        // Read all questions from the Excel file (skipping the header row)
        foreach ($sheet->getRowIterator(2, $highestRow) as $row) {
            // Read row data using rangeToArray for easier access by column letter
            $rowData = $sheet->rangeToArray('A' . $row->getRowIndex() . ':' . $sheet->getHighestColumn($row->getRowIndex()) . $row->getRowIndex(), null, true, true, true);
            $cells = $rowData[$row->getRowIndex()]; // Get array for the current row, indexed by column letter ('A', 'B', etc.)

            if ($question_type == 'descriptive') {
                 // Expecting: Section(A), Question(B), Marks(C)
                 if (isset($cells['A'], $cells['B'], $cells['C']) && !empty(trim($cells['B']))) {
                    $marks_value = filter_var(trim($cells['C']), FILTER_VALIDATE_INT);
                    if ($marks_value !== false && $marks_value > 0) {
                        $questions_from_file[] = [
                            'section' => trim($cells['A']),
                            'question' => trim($cells['B']),
                            'marks' => $marks_value,
                        ];
                        $total_marks_available += $marks_value;
                    }
                 }
            } elseif ($question_type == 'mcq') {
                 // Expecting: Question(A), OptionA(B), OptionB(C), OptionC(D), OptionD(E), CorrectOption(F), Marks(G)
                 if (isset($cells['A'], $cells['B'], $cells['C'], $cells['D'], $cells['E'], $cells['F'], $cells['G']) && !empty(trim($cells['A']))) {
                      $marks_value = filter_var(trim($cells['G']), FILTER_VALIDATE_INT);
                      $correct_option = strtoupper(trim($cells['F']));
                      // Validate correct option is one of A, B, C, D
                      if ($marks_value !== false && $marks_value > 0 && in_array($correct_option, ['A', 'B', 'C', 'D'])) {
                           $questions_from_file[] = [
                               'question' => trim($cells['A']),
                               'options' => [
                                   'A' => trim($cells['B']),
                                   'B' => trim($cells['C']),
                                   'C' => trim($cells['D']),
                                   'D' => trim($cells['E']),
                               ],
                               'correct' => $correct_option,
                               'marks' => $marks_value,
                           ];
                           $total_marks_available += $marks_value;
                      }
                 }
            }
        } // End row iteration

        if (empty($questions_from_file)) {
             throw new Exception("The uploaded Excel file contains no valid questions in the expected format for the selected question type.");
        }

        // --- Logic to generate a ~50-mark question paper ---
        $target_marks = 50;
        if ($total_marks_available < $target_marks) {
            throw new Exception("The question bank (Total: $total_marks_available marks) does not have enough marks to generate a $target_marks-mark paper.");
        }

        $selected_questions = [];
        $total_marks_generated = 0;
        shuffle($questions_from_file);

        foreach ($questions_from_file as $q) {
            if (($total_marks_generated + $q['marks']) <= $target_marks) {
                $selected_questions[] = $q;
                $total_marks_generated += $q['marks'];
            }
             // Stop if we hit the target exactly or are very close
             if ($total_marks_generated >= $target_marks - 5) { // Adjust threshold if needed
                 // break; // Optional: Stop early if close enough
             }
        }

         // Check if enough marks were selected
         if ($total_marks_generated < $target_marks * 0.8) { // e.g., require at least 40 marks for a 50 mark paper
             throw new Exception("Could not generate a paper close enough to $target_marks marks (Generated: $total_marks_generated marks). Try adding more questions or questions with smaller mark values.");
         }


        // --- Save the generated paper to the database ---
        $staff_id = $_SESSION['user_id'];
        $title = "QP-" . date('Ymd-His');
        $content = json_encode($selected_questions); // Store questions as JSON

        // Include question_type in the INSERT
        $sql = "INSERT INTO question_papers (staff_id, subject_id, title, content, exam_time, question_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt->execute([$staff_id, $subject_id, $title, $content, $exam_time, $question_type])) {
             $feedback_message = "✅ Question Paper (Type: ".strtoupper($question_type).", Total Marks: $total_marks_generated) generated and saved successfully!";
             $message_type = "success";
        } else {
             throw new Exception("Database error: Could not save the question paper.");
        }

    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_FILES['question_bank']) && $_FILES['question_bank']['error'] !== UPLOAD_ERR_OK) {
             throw new Exception("File upload failed. Error code: " . $_FILES['question_bank']['error']);
        }
    }

} catch (PDOException $e) {
    $feedback_message = "❌ Database Error: " . htmlspecialchars($e->getMessage());
} catch (Exception $e) {
    $feedback_message = "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Question Paper</title>
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
        .template-links { display: flex; justify-content: space-around; margin-top: 10px; margin-bottom: 10px; font-size: 0.9em;}
        .template-links a { color: #007bff; }
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

        <?php if (!empty($feedback_message)) {
             echo "<div class='" . htmlspecialchars($message_type) . "-message'>" . $feedback_message . "</div>";
        }?>

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

             <label for="question_type">3. Question Type:</label>
             <select name="question_type" id="question_type" required>
                 <option value="" disabled selected>-- Select Type --</option>
                 <option value="descriptive">Descriptive / Full Length</option>
                 <option value="mcq">Multiple Choice Questions (MCQ)</option>
             </select>

            <label for="question_bank">4. Upload Question Bank (Excel File):</label>
            <input type="file" id="question_bank" name="question_bank" accept=".xlsx, .xls" required>

            <div class="template-links">
                 <a href="sample.xlsx" download>Download Descriptive Template (.xlsx)</a>
                 <a href="mcq-sample.xlsx" download>Download MCQ Template (.xlsx)</a>
            </div>

            <button type="submit">Upload & Generate Paper</button>
        </form>
    </div>
</body>
</html>

