<?php
session_start();
include('db-config.php'); // Database connection
require 'vendor/autoload.php'; // Load PhpSpreadsheet library

use PhpOffice\PhpSpreadsheet\IOFactory;

// Check user role (Only Staff & Principal can access)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'principal'])) {
    die("Unauthorized access");
}

// Fetch subjects for selection
$subjects = [];
$result = $conn->query("SELECT id, name FROM subjects");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['question_bank'])) {
    if (!isset($_POST['subject_id']) || !isset($_POST['exam_time'])) {
        die("⚠️ Error: Subject and Exam Time are required.");
    }

    $file = $_FILES['question_bank']['tmp_name'];
    $subject_id = $_POST['subject_id']; // Selected subject
    $exam_time = $_POST['exam_time']; // Exam duration

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $questions = [];

    // Read Excel data
    foreach ($sheet->getRowIterator(2) as $row) { // Assuming first row is header
        $cells = $row->getCellIterator();
        $cells->setIterateOnlyExistingCells(false);
        $data = [];
        foreach ($cells as $cell) {
            $data[] = $cell->getValue();
        }
        if (count($data) >= 3) {
            $questions[] = [
                'section' => $data[0],
                'question' => $data[1],
                'marks' => (int) $data[2]
            ];
        }
    }

    // Generate Question Paper
    $selected_questions = [];
    $total_marks = 0;
    $sections = array_unique(array_column($questions, 'section'));

    foreach ($sections as $section) {
        $section_questions = array_filter($questions, fn($q) => $q['section'] == $section);
        shuffle($section_questions);

        foreach ($section_questions as $q) {
            if ($total_marks + $q['marks'] <= 50) {
                $selected_questions[] = $q;
                $total_marks += $q['marks'];
            }
        }
    }

    // Save to Database
    $creator = $_SESSION['user_id'];
    $title = "Generated Question Paper";
    $content = json_encode($selected_questions);

    $stmt = $conn->prepare("INSERT INTO question_papers (creator_id, subject_id, title, content, exam_time) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissi", $creator, $subject_id, $title, $content, $exam_time);
    $stmt->execute();
    $stmt->close();

    echo "✅ Question Paper Generated Successfully.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Question Bank</title>
</head>
<body>
    <h2>Upload Excel File for Automatic Question Paper Generation</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="subject_id">Select Subject:</label>
        <select name="subject_id" required>
            <option value="">-- Select Subject --</option>
            <?php foreach ($subjects as $subject): ?>
                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label for="exam_time">Exam Time (minutes):</label>
        <input type="number" name="exam_time" required min="30" max="180">
        <br><br>

        <input type="file" name="question_bank" required>
        <button type="submit">Upload & Generate</button>
    </form>
</body>
</html>
