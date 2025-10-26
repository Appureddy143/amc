<?php
session_start();
// Use ../ to go up one directory
include('../db-config.php'); 

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: student-login.php'); 
    exit;
}
$student_id = $_SESSION['student_id'];

// Get assignment ID from URL
$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
if (!$assignment_id) {
    die("Invalid assignment ID.");
}

$test_details = null;
$questions = [];
$message = '';
$message_type = 'error';

try {
    // Fetch assignment details and question paper content
    $sql = "SELECT at.id as assignment_id, at.status, at.grade, 
                   qp.title, qp.content, qp.exam_time, qp.question_type, 
                   s.name as subject_name
            FROM assigned_tests at
            JOIN question_papers qp ON at.question_paper_id = qp.id
            JOIN subjects s ON qp.subject_id = s.id
            WHERE at.id = ? AND at.student_id = ?"; // Ensure it belongs to this student
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$assignment_id, $student_id]);
    $test_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test_details) {
        die("Test assignment not found or does not belong to you.");
    }

    // Decode the questions from JSON content
    $questions = json_decode($test_details['content'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
        die("Error decoding question paper content.");
    }

    // --- Update status to 'started' if it was 'assigned' when page loads ---
    if ($test_details['status'] == 'assigned') {
        $update_status_sql = "UPDATE assigned_tests SET status = 'started' WHERE id = ? AND status = 'assigned'";
        $stmt_update = $conn->prepare($update_status_sql);
        $stmt_update->execute([$assignment_id]);
        $test_details['status'] = 'started'; // Update status for current page view
    }

    // --- Handle Test Submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $test_details['status'] == 'started') {
        $answers = $_POST['answers'] ?? []; // Array of answers keyed by question index

        // Validate answers (basic check)
        if (empty($answers) || count($answers) !== count($questions)) {
             $message = "Please answer all questions before submitting.";
        } else {
            $conn->beginTransaction();

            // 1. Save answers to student_submissions table
            $answers_json = json_encode($answers);
            $sub_sql = "INSERT INTO student_submissions (assigned_test_id, answers) VALUES (?, ?)";
            $stmt_sub = $conn->prepare($sub_sql);
            $stmt_sub->execute([$assignment_id, $answers_json]);

            // 2. Update assigned_tests status to 'submitted'
            $status_sql = "UPDATE assigned_tests SET status = 'submitted' WHERE id = ? AND status = 'started'";
            $stmt_status = $conn->prepare($status_sql);
            $stmt_status->execute([$assignment_id]);

            $conn->commit();
            
            $message = "Test submitted successfully!";
            $message_type = "success";
            $test_details['status'] = 'submitted'; // Update status for display

             // Optional: Redirect after submission
             // header("refresh:3;url=take-test.php"); 
        }
    }

} catch (PDOException $e) {
     if ($conn->inTransaction()) { $conn->rollBack(); }
    $message = "Database Error: " . $e->getMessage();
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Test: <?= htmlspecialchars($test_details['title'] ?? 'Test') ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; }
        .container { width: 90%; max-width: 900px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #444; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; }
        .test-info { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .question-block { margin-bottom: 25px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #fdfdfd; }
        .question-text { font-weight: bold; margin-bottom: 10px; display: block; }
        .marks { font-size: 0.9em; color: #555; float: right; }
        .options label { display: block; margin-bottom: 8px; margin-left: 5px; }
        .options input[type="radio"] { margin-right: 8px; }
        textarea { width: 100%; min-height: 80px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; box-sizing: border-box; margin-top: 5px; }
        button[type="submit"] { background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s; margin-top: 20px; }
        button[type="submit"]:hover { background-color: #218838; }
        button[type="submit"]:disabled { background-color: #6c757d; cursor: not-allowed; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid transparent; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .status-info { font-weight: bold; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px; }
        .status-info.submitted { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;}
        .status-info.graded { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;}
    </style>
</head>
<body>
    <div class="navbar">
        <a href="take-test.php">Back to Assigned Tests</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <?php if ($test_details): ?>
            <h2><?= htmlspecialchars($test_details['title']) ?></h2>
            <div class="test-info">
                <strong>Subject:</strong> <?= htmlspecialchars($test_details['subject_name']) ?><br>
                <strong>Time Allowed:</strong> <?= htmlspecialchars($test_details['exam_time']) ?> minutes<br>
                 <strong>Status:</strong> <?= htmlspecialchars(ucfirst($test_details['status'])) ?>
                 <?php if ($test_details['status'] == 'graded' && isset($test_details['grade'])): ?>
                    <br><strong>Grade:</strong> <?= htmlspecialchars($test_details['grade']) ?>
                 <?php endif; ?>
            </div>

            <?php if (!empty($message)) { echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>"; } ?>

            <?php if ($test_details['status'] == 'submitted'): ?>
                 <div class="status-info submitted">You have already submitted this test. Awaiting grading.</div>
                 <a href="take-test.php">Return to Assigned Tests</a>
            <?php elseif ($test_details['status'] == 'graded'): ?>
                 <div class="status-info graded">This test has been graded. Your score is: <?= htmlspecialchars($test_details['grade']) ?></div>
                 <a href="take-test.php">Return to Assigned Tests</a>
             <?php elseif ($test_details['status'] == 'started' && !empty($questions)): ?>
                <form action="do-test.php?assignment_id=<?= $assignment_id ?>" method="POST">
                    <h3>Answer the following questions:</h3>
                    
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="question-block">
                            <span class="marks">(<?= htmlspecialchars($q['marks']) ?> Marks)</span>
                            <span class="question-text">Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question']) ?></span>

                            <?php if ($test_details['question_type'] == 'mcq'): ?>
                                <div class="options">
                                    <?php foreach ($q['options'] as $key => $option): ?>
                                        <label>
                                            <input type="radio" name="answers[<?= $index ?>]" value="<?= $key ?>" required>
                                            <?= htmlspecialchars($option) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($test_details['question_type'] == 'descriptive'): ?>
                                 <textarea name="answers[<?= $index ?>]" placeholder="Enter your answer here..." required></textarea>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" onclick="return confirm('Are you sure you want to submit your answers? This cannot be undone.')">Submit Test</button>
                </form>
             <?php else: ?>
                 <p>There was an issue loading the test questions or the test status is invalid.</p>
                 <a href="take-test.php">Return to Assigned Tests</a>
             <?php endif; ?>

        <?php else: ?>
            <h2>Error</h2>
            <p>Could not load test details.</p>
             <a href="take-test.php">Return to Assigned Tests</a>
        <?php endif; ?>
    </div>
</body>
</html>
