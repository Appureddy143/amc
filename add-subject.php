<?php
session_start();
include('db-config.php'); // Include your PDO database connection

// Check if the user is logged in as an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Initialize a variable to hold feedback messages
$feedback_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the common data for all subjects
    $subjects = $_POST['subjects'] ?? [];
    $branch = trim($_POST['branch']);
    $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);

    // Validate that we have subjects and other required data
    if (empty($subjects) || !$branch || !$semester || !$year) {
        $feedback_message = "<p class='error-message'>❌ Please fill in all fields, including at least one subject.</p>";
    } else {
        try {
            // Start a transaction
            $conn->beginTransaction();

            // Prepare the SQL statement once, outside the loop for efficiency
            $sql = "INSERT INTO subjects (name, subject_code, branch, semester, year) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Loop through each submitted subject and execute the prepared statement
            foreach ($subjects as $subject) {
                $subject_name = trim($subject['subject_name']);
                $subject_code = trim($subject['subject_code']);

                // Ensure subject name and code are not empty
                if (!empty($subject_name) && !empty($subject_code)) {
                    $stmt->execute([$subject_name, $subject_code, $branch, $semester, $year]);
                }
            }

            // If everything was successful, commit the transaction
            $conn->commit();
            $feedback_message = "<p class='success-message'>✅ Subjects added successfully!</p>";

        } catch (PDOException $e) {
            // If any error occurs, roll back the transaction
            $conn->rollBack();
            $feedback_message = "<p class='error-message'>❌ Database Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subjects</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7fc; margin: 0; padding: 0; }
        .navbar { background-color: #333; color: #fff; padding: 12px; text-align: right; }
        .navbar a { color: #fff; text-decoration: none; margin: 0 15px; font-size: 16px; }
        .content { max-width: 800px; width: 90%; margin: 40px auto; background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; font-size: 24px; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 16px; margin-bottom: 8px; color: #555; font-weight: bold; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; font-size: 16px; background-color: #28a745; color: #fff; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; transition: background-color 0.3s ease; }
        button:hover { background-color: #218838; }
        .subject-form { margin-bottom: 20px; border: 1px solid #eee; padding: 15px; border-radius: 5px; position: relative; }
        .add-btn, .remove-btn { font-size: 24px; cursor: pointer; color: #007bff; border: none; background: none; padding: 5px; }
        .remove-btn { color: #dc3545; }
        .actions { text-align: right; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="admin-panel.php">Back to Admin Dashboard</a>
    </div>

    <div class="content">
        <h2>Add New Subjects</h2>
        
        <!-- Display feedback messages here -->
        <?php if (!empty($feedback_message)) echo $feedback_message; ?>

        <form action="add-subject.php" method="POST">
            <div class="form-group">
                <label for="branch">Branch:</label>
                <select name="branch" id="branch" required>
                    <option value="" disabled selected>-- Select a Branch --</option>
                    <option value="CSE">Computer Science</option>
                    <option value="ECE">Electronics</option>
                    <option value="MECH">Mechanical</option>
                    <option value="CIVIL">Civil</option>
                </select>
            </div>
            <div class="form-group">
                <label for="semester">Semester:</label>
                <input type="number" name="semester" id="semester" min="1" max="8" required>
            </div>
            <div class="form-group">
                <label for="year">Year:</label>
                <input type="number" name="year" id="year" min="1" max="4" required>
            </div>

            <hr>
            <h3>Subjects</h3>
            <div id="subject-form-container">
                <!-- Initial subject form -->
                <div class="subject-form">
                    <div class="form-group">
                        <label>Subject Name:</label>
                        <input type="text" name="subjects[0][subject_name]" required>
                    </div>
                    <div class="form-group">
                        <label>Subject Code:</label>
                        <input type="text" name="subjects[0][subject_code]" required>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="add-btn" onclick="addSubjectForm()">&#43;</button>
            </div>

            <button type="submit">Add All Subjects</button>
        </form>
    </div>

    <script>
        let subjectCount = 1;
        const maxSubjects = 10;

        function addSubjectForm() {
            if (subjectCount >= maxSubjects) {
                alert('You can add a maximum of ' + maxSubjects + ' subjects at a time.');
                return;
            }

            const container = document.getElementById('subject-form-container');
            const newSubjectForm = document.createElement('div');
            newSubjectForm.classList.add('subject-form');
            newSubjectForm.innerHTML = `
                <div class="form-group">
                    <label>Subject Name:</label>
                    <input type="text" name="subjects[${subjectCount}][subject_name]" required>
                </div>
                <div class="form-group">
                    <label>Subject Code:</label>
                    <input type="text" name="subjects[${subjectCount}][subject_code]" required>
                </div>
                <div class="actions">
                     <button type="button" class="remove-btn" onclick="this.parentElement.parentElement.remove()">&#8722;</button>
                </div>
            `;
            container.appendChild(newSubjectForm);
            subjectCount++;
        }
    </script>
</body>
</html>
