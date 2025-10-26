<?php
session_start();
include('db-config.php'); 

// Ensure only staff or HOD can access
$allowed_roles = ['staff', 'hod'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}
$staff_id = $_SESSION['user_id'];

// Determine the correct dashboard link based on role
$dashboard_link = match ($_SESSION['role']) {
    'staff' => 'staff_dashboard.php',
    'hod' => 'hod_dashboard.php',
    default => 'login.php', // Fallback
};

$message = ""; 
$message_type = "error"; 
$allocated_subjects = [];
$students_in_class = [];
$selected_subject_id = null;
$selected_date = date('Y-m-d'); // Default to today

try {
    // --- STEP 1: Fetch Subjects Allocated to Staff ---
    // Fetch subjects allocated to the current staff member
    $stmt_subjects = $conn->prepare("
        SELECT s.id, s.name, s.subject_code, s.branch, s.semester 
        FROM subjects s
        JOIN subject_allocation sa ON s.id = sa.subject_id
        WHERE sa.staff_id = ?
        ORDER BY s.semester, s.name
    ");
    $stmt_subjects->execute([$staff_id]);
    $allocated_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

    // --- STEP 2: Handle Selection of Subject and Date (via GET) ---
    if (isset($_GET['subject_id']) && isset($_GET['attendance_date'])) {
        $selected_subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);
        $selected_date = date('Y-m-d', strtotime($_GET['attendance_date'])); // Validate/format date

        // Find the selected subject details to get branch/semester
        $selected_subject_details = null;
        foreach ($allocated_subjects as $subj) {
            if ($subj['id'] == $selected_subject_id) {
                $selected_subject_details = $subj;
                break;
            }
        }

        if ($selected_subject_id && $selected_subject_details) {
            // --- Fetch Students for the selected class (based on subject's branch & semester) ---
            // Assumes students table has 'branch' and needs 'semester' column added, or requires a different join
            // For now, filtering by BRANCH only as student table has branch. Add semester filter if available.
             $stmt_students = $conn->prepare("
                 SELECT id, name, usn 
                 FROM students 
                 WHERE branch = ? 
                 -- AND semester = ? -- Add this if semester column exists on students table
                 ORDER BY name ASC
             ");
             // Execute with branch (and semester if added)
             $student_params = [$selected_subject_details['branch']];
             // if (isset($selected_subject_details['semester'])) { $student_params[] = $selected_subject_details['semester']; }
             $stmt_students->execute($student_params);
             $students_in_class = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
        } else {
             $message = "Invalid subject selected or subject not allocated to you.";
        }
    }
    
    // --- STEP 3: Handle Submission of Attendance (via POST) ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
         $submitted_subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
         $submitted_date = date('Y-m-d', strtotime($_POST['attendance_date']));
         $present_student_ids = $_POST['student_ids'] ?? []; // All students displayed
         $absent_student_ids = $_POST['is_absent'] ?? [];   // Only IDs of those marked absent

         if ($submitted_subject_id && $submitted_date && !empty($present_student_ids)) {
             $conn->beginTransaction();

             $sql_upsert = "INSERT INTO daily_attendance (student_id, subject_id, attendance_date, status, marked_by_staff_id) 
                            VALUES (?, ?, ?, ?, ?)
                            ON CONFLICT (student_id, subject_id, attendance_date) 
                            DO UPDATE SET 
                                status = EXCLUDED.status, 
                                marked_by_staff_id = EXCLUDED.marked_by_staff_id";
             $stmt_upsert = $conn->prepare($sql_upsert);
             
             $success_count = 0;
             foreach ($present_student_ids as $student_id) {
                  $student_id_int = filter_var($student_id, FILTER_VALIDATE_INT);
                  if($student_id_int) {
                      // Determine status: Present unless checked in absent list
                      $status = in_array($student_id, $absent_student_ids) ? 'Absent' : 'Present';
                      
                      if($stmt_upsert->execute([$student_id_int, $submitted_subject_id, $submitted_date, $status, $staff_id])) {
                          $success_count++;
                      } else {
                           // If one fails, roll back all
                           $conn->rollBack();
                           throw new PDOException("Failed to save attendance for student ID: $student_id_int");
                      }
                  }
             }

             $conn->commit();
             $message = "Attendance for " . $success_count . " students recorded successfully for " . date('d-M-Y', strtotime($submitted_date)) . ".";
             $message_type = "success";
             // Clear selection to go back to step 1
             $selected_subject_id = null; 
             $students_in_class = [];

         } else {
              $message = "Invalid data submitted.";
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
    <title>Daily Attendance</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; }
        .container { width: 90%; max-width: 800px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #444; text-align: center; margin-bottom: 1.5em;}
        label { font-weight: bold; color: #555; margin-bottom: 5px; display: block; }
        input[type="date"], select { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 16px; box-sizing: border-box; border: 1px solid #ddd; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: #0056b3; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid transparent; }
        .message.success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .student-list table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        .student-list th, .student-list td { border: 1px solid #ddd; padding: 0.75em; text-align: left; }
        .student-list th { background-color: #eee; }
        .student-list td.actions { text-align: center; }
        .student-list input[type="checkbox"] { width: 1.3em; height: 1.3em; } /* Larger checkbox */
        .submit-attendance-btn { background-color: #28a745; margin-top: 20px; font-weight: bold; }
        .submit-attendance-btn:hover { background-color: #218838; }
        .back-link { display: block; margin-top: 20px; text-align: center; color: #007bff; }
        hr { margin: 30px 0; border: 0; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="<?= htmlspecialchars($dashboard_link) ?>">Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>Take Daily Attendance</h2>

        <?php if (!empty($message)) { echo "<div class='message " . htmlspecialchars($message_type) . "'>" . htmlspecialchars($message) . "</div>"; } ?>

        <!-- STEP 1 & 2: Select Subject and Date -->
        <form action="enter-attendance-daily.php" method="GET">
             <h3>Select Class</h3>
             <label for="attendance_date">Date:</label>
             <input type="date" id="attendance_date" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>" required>

             <label for="subject_id">Subject:</label>
             <select name="subject_id" id="subject_id" required>
                 <option value="" disabled <?= !$selected_subject_id ? 'selected' : '' ?>>-- Select Subject --</option>
                 <?php foreach ($allocated_subjects as $subject): ?>
                     <option value="<?= $subject['id'] ?>" <?= ($selected_subject_id == $subject['id']) ? 'selected' : '' ?>>
                         <?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['name'] . ' (Sem ' . $subject['semester'] . ', ' . $subject['branch'] . ')') ?>
                     </option>
                 <?php endforeach; ?>
                  <?php if (empty($allocated_subjects)): ?>
                     <option value="" disabled>No subjects allocated to you.</option>
                 <?php endif; ?>
             </select>
             
             <button type="submit">Load Student List</button>
        </form>

        <?php if ($selected_subject_id && !empty($students_in_class)): ?>
            <hr>
            <!-- STEP 3: Mark Absentees and Submit -->
            <form action="enter-attendance-daily.php" method="POST" class="student-list">
                 <h3>Mark Absentees for <?= date('d-M-Y', strtotime($selected_date)) ?></h3>
                 <input type="hidden" name="subject_id" value="<?= htmlspecialchars($selected_subject_id) ?>">
                 <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">
                 
                 <table>
                     <thead>
                         <tr>
                             <th>USN</th>
                             <th>Student Name</th>
                             <th>Mark Absent</th>
                         </tr>
                     </thead>
                     <tbody>
                         <?php foreach ($students_in_class as $student): ?>
                             <tr>
                                 <td><?= htmlspecialchars($student['usn']) ?></td>
                                 <td><?= htmlspecialchars($student['name']) ?></td>
                                 <td class="actions">
                                     <!-- Hidden input containing all student IDs -->
                                     <input type="hidden" name="student_ids[]" value="<?= $student['id'] ?>">
                                     <!-- Checkbox for marking absent, value is the student ID -->
                                     <input type="checkbox" name="is_absent[]" value="<?= $student['id'] ?>" title="Check if Absent">
                                 </td>
                             </tr>
                         <?php endforeach; ?>
                     </tbody>
                 </table>
                 
                 <button type="submit" name="submit_attendance" class="submit-attendance-btn" onclick="return confirm('Submit attendance for <?= count($students_in_class) ?> students?')">Submit Attendance</button>
            </form>
        
        <?php elseif ($selected_subject_id && empty($students_in_class)): ?>
             <hr>
             <p style="text-align:center; color: #777;">No students found for the selected subject's branch/semester.</p>
        <?php endif; ?>
        
        <a href="<?= htmlspecialchars($dashboard_link) ?>" class="back-link">Cancel</a>
    </div>
</body>
</html>
