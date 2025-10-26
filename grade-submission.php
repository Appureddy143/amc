<?php
session_start();
include('db-config.php'); 

// Ensure only staff/hod/admin can grade
$allowed_roles = ['staff', 'hod', 'admin', 'principal'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}
$grader_staff_id = $_SESSION['user_id']; // Logged in staff member

// Determine the correct dashboard link
$dashboard_link = match ($_SESSION['role']) {
    'admin' => 'admin_dashboard.php',
    'staff' => 'staff_dashboard.php',
    'hod' => 'hod_dashboard.php',    
    'principal' => 'principal_dashboard.php', 
    default => 'login.php', 
};

// Get assignment ID from URL
$assignment_id = filter_input(INPUT_GET, 'assignment_id', FILTER_VALIDATE_INT);
if (!$assignment_id) {
    die("Invalid assignment ID.");
}

$test_details = null;
$questions = [];
$student_answers = [];
$submission_details = null;
$message = '';
$message_type = 'error';

try {
    // Fetch assignment, paper, subject, student, and submission details
    $sql = "SELECT 
                at.id as assignment_id, at.status, at.grade, at.question_paper_id, at.student_id,
                qp.title, qp.content, qp.question_type,
                s.name as subject_name,
                st.name as student_name, st.usn,
                sub.answers, sub.submitted_at
            FROM assigned_tests at
            JOIN question_papers qp ON at.question_paper_id = qp.id
            JOIN subjects s ON qp.subject_id = s.id
            JOIN students st ON at.student_id = st.id
            LEFT JOIN student_submissions sub ON at.id = sub.assigned_test_id -- Left join in case submission failed but status is submitted
            WHERE at.id = ? AND at.assigned_by_staff_id = ?"; // Ensure grader is the one who assigned it
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$assignment_id, $grader_staff_id]);
    $test_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test_details) {
        die("Submission not found, does not belong to you, or was not assigned by you.");
    }
     if ($test_details['status'] != 'submitted') {
         // Redirect if already graded or not submitted yet
         header("Location: correct-test.php?msg=already_graded_or_not_submitted");
         exit;
     }

    // Decode questions and answers
    $questions = json_decode($test_details['content'], true);
    $student_answers = $test_details['answers'] ? json_decode($test_details['answers'], true) : [];

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
        die("Error decoding question paper content.");
    }
     if (json_last_error() !== JSON_ERROR_NONE || !is_array($student_answers)) {
         die("Error decoding student answers.");
     }

    // --- Handle Grade Submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $final_grade = 0;
        $feedback_data = $_POST['feedback'] ?? []; // Array potentially holding marks for descriptive

        // Calculate grade
        foreach ($questions as $index => $q) {
            if ($test_details['question_type'] == 'mcq') {
                 // Auto-grade MCQs again for verification (or trust stored answers)
                 $student_choice = $student_answers[$index] ?? null;
                 if (isset($q['correct']) && $student_choice === $q['correct']) {
                     $final_grade += $q['marks'];
                 }
            } elseif ($test_details['question_type'] == 'descriptive') {
                 // Grade descriptive based on staff input ('correct'/'wrong' could map to marks)
                 $mark_awarded = filter_var($feedback_data[$index] ?? 0, FILTER_VALIDATE_INT);
                 // Ensure awarded marks don't exceed question marks
                 if ($mark_awarded !== false && $mark_awarded >= 0 && $mark_awarded <= $q['marks']) {
                     $final_grade += $mark_awarded;
                 } elseif (isset($_POST['grade_choice'][$index]) && $_POST['grade_choice'][$index] == 'correct') {
                     // Alternative: If using 'correct'/'wrong' buttons
                     $final_grade += $q['marks'];
                 }
            }
        }

        // --- Update Database ---
        $update_sql = "UPDATE assigned_tests SET status = 'graded', grade = ?, graded_at = NOW() WHERE id = ? AND status = 'submitted'";
        $stmt_update = $conn->prepare($update_sql);
        
        if ($stmt_update->execute([$final_grade, $assignment_id])) {
            $message = "Test graded successfully! Final score: " . $final_grade;
            $message_type = "success";
            $test_details['status'] = 'graded'; // Update status for display
            $test_details['grade'] = $final_grade;
             header("refresh:3;url=correct-test.php"); // Redirect back after 3 seconds
        } else {
             $message = "Error saving grade to database.";
             $message_type = "error";
        }
    }


} catch (PDOException $e) {
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
    <title>Grade Submission</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; }
        .container { width: 90%; max-width: 900px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #444; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; }
        .submission-info { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
        .submission-info strong { color: #555; }
        
        .question-block { margin-bottom: 25px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #fdfdfd; }
        .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .question-text { font-weight: bold; display: block; flex-grow: 1; margin-right: 10px;}
        .marks { font-size: 0.9em; color: #555; white-space: nowrap; }
        
        .student-answer { margin-top: 10px; padding: 10px; background-color: #e9ecef; border-left: 3px solid #007bff; border-radius: 4px; }
        .student-answer.mcq { font-style: italic; }
        
        .grading-options { margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 15px; }
        .grading-options label { margin-right: 15px; font-weight: bold; }
        .grading-options input[type="number"] { width: 60px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
        .grading-options button { padding: 5px 10px; border-radius: 4px; border: none; cursor: pointer; color: white; margin-left: 5px; }
        .correct-btn { background-color: #28a745; }
        .wrong-btn { background-color: #dc3545; }
        
        .correct-answer-mcq { color: green; font-weight: bold; margin-top: 5px; }
        .incorrect-answer-mcq { color: red; }

        button[type="submit"] { background-color: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s; margin-top: 30px; font-weight: bold; display: block; width: 100%; }
        button[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid transparent; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .back-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }

    </style>
</head>
<body>
    <div class="navbar">
        <a href="correct-test.php">Back to Submissions</a>
        <a href="<?= htmlspecialchars($dashboard_link) ?>">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
         <?php if (!empty($message)) { echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>"; } ?>

        <?php if ($test_details && $test_details['status'] == 'graded'): ?>
             <h2>Grading Complete</h2>
             <p>This test has already been graded.</p>
             <p>Final Score: <?= htmlspecialchars($test_details['grade']) ?></p>
             <a href="correct-test.php" class="back-link">Return to Submissions List</a>

        <?php elseif ($test_details && $test_details['status'] == 'submitted'): ?>
            <h2>Grade Submission</h2>
            <div class="submission-info">
                <strong>Student:</strong> <?= htmlspecialchars($test_details['student_name']) ?> (<?= htmlspecialchars($test_details['usn']) ?>)<br>
                <strong>Subject:</strong> <?= htmlspecialchars($test_details['subject_name']) ?><br>
                <strong>Test:</strong> <?= htmlspecialchars($test_details['title']) ?><br>
                <strong>Submitted At:</strong> <?= htmlspecialchars(date('d-M-Y H:i', strtotime($test_details['submitted_at']))) ?>
            </div>

            <form action="grade-submission.php?assignment_id=<?= $assignment_id ?>" method="POST">
                <h3>Student Answers:</h3>
                
                <?php foreach ($questions as $index => $q): ?>
                    <div class="question-block">
                        <div class="question-header">
                            <span class="question-text">Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question']) ?></span>
                            <span class="marks">(<?= htmlspecialchars($q['marks']) ?> Marks)</span>
                        </div>
                        
                        <div class="student-answer <?= $test_details['question_type'] == 'mcq' ? 'mcq' : '' ?>">
                            <strong>Answer:</strong> 
                            <?php 
                            $student_ans = $student_answers[$index] ?? '[No Answer Provided]';
                            if ($test_details['question_type'] == 'mcq') {
                                // Display the text of the chosen option
                                $option_key = $student_ans;
                                echo isset($q['options'][$option_key]) ? htmlspecialchars($q['options'][$option_key]) . " ($option_key)" : '[Invalid Option]';
                            } else {
                                echo nl2br(htmlspecialchars($student_ans)); // nl2br for descriptive answers
                            }
                            ?>
                        </div>

                        <?php if ($test_details['question_type'] == 'mcq'): ?>
                             <div class="grading-options">
                                <?php if (isset($q['correct']) && $student_ans === $q['correct']): ?>
                                    <span class="correct-answer-mcq">✓ Correct</span>
                                <?php else: ?>
                                     <span class="incorrect-answer-mcq">✗ Incorrect</span><br>
                                     <span class="correct-answer-mcq">Correct Answer: <?= htmlspecialchars($q['options'][$q['correct']] ?? '[N/A]') . " ({$q['correct']})" ?></span>
                                <?php endif; ?>
                             </div>
                        <?php elseif ($test_details['question_type'] == 'descriptive'): ?>
                            <div class="grading-options">
                                <label for="feedback_<?= $index ?>">Award Marks (0 - <?= $q['marks'] ?>):</label>
                                <input type="number" name="feedback[<?= $index ?>]" id="feedback_<?= $index ?>" min="0" max="<?= $q['marks'] ?>" value="0" required>
                                
                                <!-- Alternative Correct/Wrong Buttons (Simpler grading) -->
                                <!-- 
                                <label><input type="radio" name="grade_choice[<?= $index ?>]" value="correct"> Correct (+<?= $q['marks'] ?>)</label>
                                <label><input type="radio" name="grade_choice[<?= $index ?>]" value="wrong" checked> Wrong (+0)</label> 
                                -->
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" onclick="return confirm('Finalize grading for this submission?')">Submit Grade</button>
            </form>

        <?php else: ?>
            <h2>Error</h2>
            <p>Could not load submission details or the submission is not ready for grading.</p>
            <a href="correct-test.php" class="back-link">Return to Submissions List</a>
        <?php endif; ?>
        
         <a href="correct-test.php" class="back-link">Cancel and Return to Submissions List</a>
    </div>
</body>
</html>