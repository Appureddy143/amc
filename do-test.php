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
$can_take_test = false; // Flag to control form display based on time

try {
    // Fetch assignment details and question paper content, include start/end times
    $sql = "SELECT at.id as assignment_id, at.status, at.grade, at.start_time, at.end_time,
                   qp.title, qp.content, qp.exam_time, qp.question_type,
                   s.name as subject_name
            FROM assigned_tests at
            JOIN question_papers qp ON at.question_paper_id = qp.id
            JOIN subjects s ON qp.subject_id = s.id
            WHERE at.id = ? AND at.student_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$assignment_id, $student_id]);
    $test_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test_details) {
        die("Test assignment not found or does not belong to you.");
    }

    // --- Time Validation ---
    $current_time = time();
    $start_timestamp = $test_details['start_time'] ? strtotime($test_details['start_time']) : null;
    $end_timestamp = $test_details['end_time'] ? strtotime($test_details['end_time']) : null;

    if ($start_timestamp && $current_time < $start_timestamp) {
        $message = "This test is not available until " . date('d-M-Y H:i', $start_timestamp) . ".";
        $can_take_test = false;
    } elseif ($end_timestamp && $current_time > $end_timestamp) {
        $message = "The deadline for this test (" . date('d-M-Y H:i', $end_timestamp) . ") has passed.";
        // Optionally update status to 'missed' if it was 'assigned' or 'started'
        if (in_array($test_details['status'], ['assigned', 'started'])) {
             // $conn->prepare("UPDATE assigned_tests SET status='missed' WHERE id=?")->execute([$assignment_id]);
             // $test_details['status'] = 'missed'; // Reflect change immediately
        }
        $can_take_test = false;
    } else {
        // If within time limits (or no limits set)
        $can_take_test = true;
    }

    // Allow viewing submitted/graded tests even if time is over
    if (in_array($test_details['status'], ['submitted', 'graded'])) {
         $can_take_test = false; // Cannot re-take
    }


    // Decode the questions from JSON content
    $questions = json_decode($test_details['content'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
        // Use a more user-friendly message
        $message = "Error loading test questions. Please contact your instructor.";
        $questions = []; // Prevent further processing if questions are invalid
        $can_take_test = false;
       // Optionally log the actual error: error_log("JSON Decode Error: ".json_last_error_msg()." for assignment ID: ".$assignment_id);
    }

    // --- Update status to 'started' only if allowed and was 'assigned' ---
    if ($can_take_test && $test_details['status'] == 'assigned') {
        $update_status_sql = "UPDATE assigned_tests SET status = 'started' WHERE id = ? AND status = 'assigned'";
        $stmt_update = $conn->prepare($update_status_sql);
        $stmt_update->execute([$assignment_id]);
        $test_details['status'] = 'started';
    }

    // --- Handle Test Submission ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $test_details['status'] == 'started' && $can_take_test) {
        $answers = $_POST['answers'] ?? [];

        // Check if number of answers matches number of questions (basic validation)
        $expected_answers = count($questions);
        if (empty($answers) || count($answers) !== $expected_answers) {
             // Modify message based on whether it was auto-submitted or user submitted
             $auto_submit_flag = isset($_POST['auto_submitted']) && $_POST['auto_submitted'] === 'true';
             if ($auto_submit_flag) {
                 $message = "Test automatically submitted due to switching tabs. Unanswered questions were marked incorrect.";
                 // Ensure answers array has keys for all questions, even if null
                 for ($i = 0; $i < $expected_answers; $i++) {
                     if (!isset($answers[$i])) {
                         $answers[$i] = null; // Mark unanswered as null
                     }
                 }
             } else {
                $message = "Please answer all questions before submitting.";
                // Prevent submission if manual and not all answered
                throw new Exception($message);
             }
        }

        $conn->beginTransaction();

        // 1. Save answers
        $answers_json = json_encode($answers);
        // Using ON CONFLICT to handle potential resubmissions if allowed by logic (currently prevents resubmit via status check)
        $sub_sql = "INSERT INTO student_submissions (assigned_test_id, answers) VALUES (?, ?)
                    ON CONFLICT (assigned_test_id) DO UPDATE SET answers = EXCLUDED.answers, submitted_at = NOW()";
        $stmt_sub = $conn->prepare($sub_sql);
        $stmt_sub->execute([$assignment_id, $answers_json]);

        // --- Auto-Grading for MCQs ---
        $grade = null;
        $final_status = 'submitted';

        if ($test_details['question_type'] == 'mcq') {
             $score = 0;
             $total_possible_marks = 0;
             foreach ($questions as $index => $q) {
                 $total_possible_marks += ($q['marks'] ?? 0); // Sum marks safely
                 $student_answer = $answers[$index] ?? null;
                 if (isset($q['correct']) && $student_answer === $q['correct']) {
                     $score += ($q['marks'] ?? 0); // Add marks safely
                 }
             }
             $grade = $score;
             $final_status = 'graded';
             if (!isset($auto_submit_flag) || !$auto_submit_flag) { // Don't overwrite auto-submit message
                $message = "Test submitted successfully! Your score: $score / $total_possible_marks";
             }
             $message_type = "success";
        } else {
             if (!isset($auto_submit_flag) || !$auto_submit_flag) {
                $message = "Test submitted successfully! It will be graded by your instructor.";
             } else {
                 // Adjust auto-submit message for descriptive
                 $message = "Test automatically submitted due to switching tabs. It will be graded by your instructor.";
             }
             $message_type = "success";
        }

        // 2. Update assigned_tests status and grade
        $graded_at_sql = ($final_status == 'graded') ? ", graded_at = NOW()" : "";
        $status_sql = "UPDATE assigned_tests SET status = ?, grade = ? {$graded_at_sql} WHERE id = ? AND status = 'started'"; // Only update if status was 'started'
        $stmt_status = $conn->prepare($status_sql);
        $update_successful = $stmt_status->execute([$final_status, $grade, $assignment_id]);

        if ($update_successful && $stmt_status->rowCount() > 0) { // Check if row was actually updated
            $conn->commit();
            $test_details['status'] = $final_status;
            $test_details['grade'] = $grade;
        } else {
            // Rollback if update failed or status wasn't 'started'
            $conn->rollBack();
            // Override previous success message if update failed
            $message = "Submission failed. The test might have already been submitted or an error occurred.";
            $message_type = "error";
        }


    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && ($test_details['status'] == 'submitted' || $test_details['status'] == 'graded')) {
        // Prevent resubmission if status changed between page load and POST
        $message = "This test has already been submitted or graded.";
        $message_type = "error";

    }

} catch (PDOException $e) {
     if ($conn && $conn->inTransaction()) { $conn->rollBack(); } // Check $conn exists before rollback
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
        /* Base styles */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; }
        .container { width: 90%; max-width: 900px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #444; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; }
        .test-info { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; line-height: 1.6; }
        .test-info strong { color: #555; }

        /* Messages */
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid transparent; font-weight: bold;}
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .message.info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; } /* Info style for time messages */

        /* Status Info */
        .status-info { font-weight: bold; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px; border: 1px solid transparent; }
        .status-info.submitted { background-color: #fff3cd; color: #856404; border-color: #ffeeba;}
        .status-info.graded { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb;}
        .status-info.missed { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;} /* Style for missed tests */

        /* Questions */
        .question-block { margin-bottom: 25px; padding: 15px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #fdfdfd; }
        .question-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .question-text { font-weight: bold; display: block; flex-grow: 1; margin-right: 10px;}
        .marks { font-size: 0.9em; color: #555; white-space: nowrap; }
        .options label { display: block; margin-bottom: 8px; margin-left: 5px; cursor: pointer; }
        .options input[type="radio"] { margin-right: 8px; vertical-align: middle; }
        textarea { width: 100%; min-height: 80px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 1em; box-sizing: border-box; margin-top: 5px; }

        /* Buttons */
        button[type="submit"] { background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s; margin-top: 20px; font-weight: bold;}
        button[type="submit"]:hover { background-color: #218838; }
        button[type="submit"]:disabled { background-color: #6c757d; cursor: not-allowed; }
        .back-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }

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
                <?php if ($start_timestamp): ?>
                    <strong>Available From:</strong> <?= htmlspecialchars(date('d-M-Y H:i', $start_timestamp)) ?><br>
                <?php endif; ?>
                 <?php if ($end_timestamp): ?>
                    <strong>Must Submit By:</strong> <?= htmlspecialchars(date('d-M-Y H:i', $end_timestamp)) ?><br>
                <?php endif; ?>
                 <strong>Status:</strong> <?= htmlspecialchars(ucfirst($test_details['status'])) ?>
                 <?php if ($test_details['status'] == 'graded' && isset($test_details['grade'])): ?>
                    <br><strong>Your Grade:</strong> <?= htmlspecialchars($test_details['grade']) ?>
                 <?php endif; ?>
            </div>

            <?php if (!empty($message)) {
                $msg_class = ($message_type == 'error' && !$can_take_test && !in_array($test_details['status'], ['submitted', 'graded', 'missed'])) ? 'info' : $message_type;
                echo "<div class='message " . htmlspecialchars($msg_class) . "'>" . htmlspecialchars($message) . "</div>";
            } ?>

            <?php if ($test_details['status'] == 'submitted'): ?>
                 <div class="status-info submitted">
                     You submitted this test.
                     <?= ($test_details['question_type'] == 'descriptive') ? 'Awaiting grading.' : '' ?>
                 </div>
                 <a href="take-test.php" class="back-link">Return to Assigned Tests</a>
            <?php elseif ($test_details['status'] == 'graded'): ?>
                 <div class="status-info graded">
                     This test has been graded.
                     <?= isset($test_details['grade']) ? ' Your score is: '.htmlspecialchars($test_details['grade']) : '' ?>
                 </div>
                 <a href="take-test.php" class="back-link">Return to Assigned Tests</a>
            <?php elseif ($test_details['status'] == 'missed'): ?>
                 <div class="status-info missed">The deadline for this test has passed and it was not submitted.</div>
                 <a href="take-test.php" class="back-link">Return to Assigned Tests</a>
            <?php elseif ($can_take_test && $test_details['status'] == 'started' && !empty($questions)): ?>
                <form id="testForm" action="do-test.php?assignment_id=<?= $assignment_id ?>" method="POST">
                    <!-- Add a hidden input for the auto-submit flag -->
                    <input type="hidden" name="auto_submitted" id="auto_submitted" value="false">

                    <h3>Answer the following questions:</h3>

                    <?php foreach ($questions as $index => $q): ?>
                        <div class="question-block">
                             <div class="question-header">
                                 <span class="question-text">Q<?= $index + 1 ?>: <?= htmlspecialchars($q['question']) ?></span>
                                 <span class="marks">(<?= htmlspecialchars($q['marks'] ?? '?') ?> Marks)</span>
                             </div>

                            <?php if ($test_details['question_type'] == 'mcq'): ?>
                                <div class="options">
                                    <?php
                                    $options = $q['options'] ?? [];
                                    ?>
                                    <?php foreach ($options as $key => $option): ?>
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

                    <button type="submit" id="submitButton" onclick="return confirm('Are you sure you want to submit your answers? This cannot be undone.')">Submit Test</button>
                </form>
            <?php elseif (!$can_take_test && !empty($message)): ?>
                 <!-- Message already shown above for time issues -->
                 <a href="take-test.php" class="back-link">Return to Assigned Tests</a>
            <?php else: ?>
                 <p class="message error">There was an issue loading the test questions or the test status is invalid.</p>
                 <a href="take-test.php" class="back-link">Return to Assigned Tests</a>
            <?php endif; ?>

        <?php else: ?>
            <h2>Error</h2>
            <p class="message error">Could not load test details.</p>
             <a href="take-test.php" class="back-link">Return to Assigned Tests</a>
        <?php endif; ?>
    </div>

    <?php
    // Only add the anti-cheating script if the test is currently in 'started' state and allowed
    if ($test_details && $test_details['status'] == 'started' && $can_take_test):
    ?>
    <script>
        let formSubmitted = false; // Flag to prevent multiple submissions

        // Function to submit the form automatically
        function autoSubmitTest() {
            if (!formSubmitted) {
                formSubmitted = true; // Set flag
                console.log("Tab switched - Auto-submitting test...");
                const form = document.getElementById('testForm');
                const autoSubmitInput = document.getElementById('auto_submitted');
                const submitButton = document.getElementById('submitButton');

                if (form && autoSubmitInput) {
                    autoSubmitInput.value = 'true'; // Mark as auto-submitted
                    if (submitButton) submitButton.disabled = true; // Disable manual submit button
                    form.submit();
                } else {
                    console.error("Could not find test form or auto_submitted input.");
                }
            }
        }

        // Listen for visibility changes (switching tabs, minimizing window)
        document.addEventListener('visibilitychange', () => {
            // Check if the page is hidden AND the test hasn't already been submitted by this script
            if (document.hidden && !formSubmitted) {
                autoSubmitTest();
            }
        });

         // Optional: Add listeners for window blur/focus (might trigger too often)
         // window.addEventListener('blur', autoSubmitTest);

         // Prevent multiple submissions if user clicks button manually after auto-submit starts
         const formElement = document.getElementById('testForm');
         if(formElement){
             formElement.addEventListener('submit', () => {
                 if (formSubmitted) return false; // Prevent submission if already auto-submitting
                 formSubmitted = true; // Set flag on manual submit too
                 const submitButton = document.getElementById('submitButton');
                 if (submitButton) submitButton.disabled = true;
             });
         }
    </script>
    <?php endif; ?>

</body>
</html>

