<?php
session_start();
include('db-config.php'); 

// Ensure only staff/hod/admin can access
$allowed_roles = ['staff', 'hod', 'admin', 'principal'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}
$staff_id = $_SESSION['user_id']; // Logged in staff member

// Determine the correct dashboard link based on role
$dashboard_link = match ($_SESSION['role']) {
    'admin' => 'admin_dashboard.php',
    'staff' => 'staff_dashboard.php',
    'hod' => 'hod_dashboard.php',    
    'principal' => 'principal_dashboard.php', 
    default => 'login.php', // Fallback
};

$semesters = range(1, 8);
$allocated_subjects = []; // Subjects allocated to this staff for the selected semester
$question_papers = []; // Papers created by this staff for the selected subject
$students = [];
$message = "";
$message_type = "error";
$selected_semester = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT); // Get selected semester from URL

try {
    // Fetch all students (consider filtering later)
    $stmt_students = $conn->prepare("SELECT id, name, usn FROM students ORDER BY name");
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // If a semester is selected, fetch allocated subjects for this staff
    if ($selected_semester) {
        $stmt_subjects = $conn->prepare("
            SELECT s.id, s.name, s.subject_code 
            FROM subjects s
            JOIN subject_allocation sa ON s.id = sa.subject_id
            WHERE sa.staff_id = ? AND s.semester = ?
            ORDER BY s.name
        ");
        $stmt_subjects->execute([$staff_id, $selected_semester]);
        $allocated_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch question papers created by this staff for the allocated subjects of this semester
        if (!empty($allocated_subjects)) {
             $subject_ids = array_column($allocated_subjects, 'id');
             $placeholders = implode(',', array_fill(0, count($subject_ids), '?')); // Creates ?,?,?
             
             $stmt_qp = $conn->prepare("
                 SELECT qp.id, qp.title, s.name as subject_name 
                 FROM question_papers qp 
                 JOIN subjects s ON qp.subject_id = s.id
                 WHERE qp.staff_id = ? AND qp.subject_id IN ($placeholders)
                 ORDER BY qp.title
             ");
             // Parameters: staff_id first, then the array of subject IDs
             $params = array_merge([$staff_id], $subject_ids);
             $stmt_qp->execute($params);
             $question_papers = $stmt_qp->fetchAll(PDO::FETCH_ASSOC);
        }
    }


    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $paper_id = filter_input(INPUT_POST, 'question_paper_id', FILTER_VALIDATE_INT);
        $student_ids = $_POST['student_ids'] ?? []; // Array of student IDs
        $start_time_str = trim($_POST['start_time'] ?? '');
        $end_time_str = trim($_POST['end_time'] ?? '');
        
        // Convert local datetime strings to UTC timestamps for DB
        $start_time = !empty($start_time_str) ? date('Y-m-d H:i:s', strtotime($start_time_str)) : null;
        $end_time = !empty($end_time_str) ? date('Y-m-d H:i:s', strtotime($end_time_str)) : null;


        if (!$paper_id || empty($student_ids)) {
            $message = "Please select a question paper and at least one student.";
        } elseif ($start_time && $end_time && $start_time >= $end_time) {
             $message = "End time must be after the start time.";
        } else {
            $conn->beginTransaction();
            // Added start_time, end_time
            $sql = "INSERT INTO assigned_tests (question_paper_id, student_id, assigned_by_staff_id, start_time, end_time, status) 
                    VALUES (?, ?, ?, ?, ?, 'assigned') 
                    ON CONFLICT (question_paper_id, student_id) DO NOTHING"; // Prevent duplicates
            $stmt_assign = $conn->prepare($sql);
            $assigned_count = 0;

            foreach ($student_ids as $student_id) {
                $student_id_int = filter_var($student_id, FILTER_VALIDATE_INT);
                if ($student_id_int) {
                    // Pass start_time and end_time to execute
                    if ($stmt_assign->execute([$paper_id, $student_id_int, $staff_id, $start_time, $end_time])) {
                         $assigned_count += $stmt_assign->rowCount(); 
                    }
                }
            }
            $conn->commit();
            
            if ($assigned_count > 0) {
                 $message = "Test assigned successfully to " . $assigned_count . " student(s).";
                 $message_type = "success";
            } else {
                 $message = "No new assignments made. Students may have already been assigned this test.";
                 $message_type = "error"; 
            }
            // Reset selected semester to refresh subject/paper lists if needed
             $selected_semester = null; 
             $allocated_subjects = [];
             $question_papers = [];
        }
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Test</title>
    <style>
        /* Consistent Styling */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; transition: background-color 0.3s ease; }
        .navbar a:hover { background-color: #0056b3; }
        .container { width: 90%; max-width: 800px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #444; text-align: center; margin-bottom: 25px;}
        form { display: flex; flex-direction: column; gap: 15px; }
        label { font-weight: bold; color: #555; }
        input, select, button { width: 100%; padding: 10px; border-radius: 4px; font-size: 16px; box-sizing: border-box; border: 1px solid #ddd; }
        select[multiple] { height: 150px; } 
        button { background-color: #28a745; color: white; cursor: pointer; border: none; transition: background-color 0.3s ease; font-weight: bold; margin-top: 10px; }
        button:hover { background-color: #218838; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid transparent; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .back-link { display: block; margin-top: 20px; text-align: center; color: #007bff; }
        .form-row { display: flex; gap: 15px; align-items: flex-end; }
        .form-row label, .form-row select, .form-row button { margin-bottom: 0; }
        .form-row > div { flex: 1; } /* Make elements share space */
    </style>
    <script>
        // Function to reload page with selected semester
        function filterBySemester() {
            const semesterSelect = document.getElementById('semester');
            const selectedSemester = semesterSelect.value;
            if (selectedSemester) {
                window.location.href = 'assign-test.php?semester=' + selectedSemester;
            } else {
                 window.location.href = 'assign-test.php'; // Clear selection
            }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <a href="<?= htmlspecialchars($dashboard_link) ?>">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Assign Test to Students</h2>

        <?php if (!empty($message)) { echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>"; } ?>

         <!-- Semester Selection -->
         <div class="form-row">
             <div>
                <label for="semester">1. Select Semester to Filter Subjects:</label>
                <select name="semester" id="semester" onchange="filterBySemester()">
                    <option value="">-- Select Semester --</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?= $sem ?>" <?= ($selected_semester == $sem) ? 'selected' : '' ?>>Semester <?= $sem ?></option>
                    <?php endforeach; ?>
                </select>
             </div>
             <!-- Optional: Add a button if needed, or rely on onchange -->
             <!-- <button type="button" onclick="filterBySemester()">Filter Subjects</button> -->
         </div>
         
         <hr style="margin: 20px 0;">

        <form action="assign-test.php<?= $selected_semester ? '?semester='.$selected_semester : '' ?>" method="POST">
             <!-- Hidden field to keep track of semester during POST -->
             <input type="hidden" name="selected_semester_post" value="<?= htmlspecialchars($selected_semester ?? '') ?>">

            <label for="question_paper_id">2. Select Question Paper:</label>
            <select name="question_paper_id" id="question_paper_id" required <?= !$selected_semester ? 'disabled' : '' ?>>
                <option value="" disabled selected>-- Select a Paper (Filter by Semester First) --</option>
                <?php foreach ($question_papers as $paper): ?>
                    <option value="<?= $paper['id'] ?>"><?= htmlspecialchars($paper['title'] . ' - ' . $paper['subject_name']) ?></option>
                <?php endforeach; ?>
                 <?php if ($selected_semester && empty($question_papers)): ?>
                    <option value="" disabled>No question papers found for your allocated subjects in this semester.</option>
                 <?php endif; ?>
                 <?php if (!$selected_semester): ?>
                     <option value="" disabled>Select a semester above first.</option>
                 <?php endif; ?>
            </select>

            <label for="student_ids">3. Select Student(s): (Hold Ctrl/Cmd to select multiple)</label>
            <select name="student_ids[]" id="student_ids" multiple required size="8">
                <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name'] . ' (' . $student['usn'] . ')') ?></option>
                <?php endforeach; ?>
                 <?php if (empty($students)): ?>
                    <option value="" disabled>No students found in the system.</option>
                <?php endif; ?>
            </select>
            
            <label for="start_time">4. Available From (Optional):</label>
            <input type="datetime-local" id="start_time" name="start_time">
            
            <label for="end_time">5. Due By (Optional):</label>
            <input type="datetime-local" id="end_time" name="end_time">


            <button type="submit" <?= !$selected_semester || empty($question_papers) ? 'disabled' : '' ?>>Assign Test</button>
        </form>
         <a href="<?= htmlspecialchars($dashboard_link) ?>" class="back-link">Cancel</a>
    </div>
</body>
</html>