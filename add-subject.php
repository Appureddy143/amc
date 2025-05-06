<?php
session_start();
include('db-config.php'); // Include database connection

// Check if the user is logged in as admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process each subject added
    $subjects = $_POST['subjects'];
    $branch = $_POST['branch'];
    
    // Loop through each subject and insert it into the database
    foreach ($subjects as $subject) {
        $subject_name = $subject['subject_name'];
        $subject_code = $subject['subject_code'];
        
        // Insert into subjects table
        $query = "INSERT INTO subjects (name, subject_code, branch) VALUES ('$subject_name', '$subject_code', '$branch')";

        
        if (!$conn->query($query)) {
            echo "Error: " . $conn->error;
            exit;
        }
    }
    
    echo "Subjects added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subjects</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #333;
            color: #fff;
            padding: 12px;
            text-align: center;
        }

        .navbar a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            font-size: 18px;
        }

        .content {
            max-width: 800px;
            width: 90%;
            margin: 40px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-size: 16px;
            margin-bottom: 8px;
            color: #555;
        }

        input[type="text"], select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            margin-top: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background-color: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Subject Form Container */
        .subject-form-container {
            margin-top: 20px;
        }

        .subject-form {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .plus-icon, .remove-icon {
            font-size: 20px;
            cursor: pointer;
            color: #4CAF50;
            margin-top: 10px;
            display: inline-block;
            margin-left: 15px;
        }

        .remove-icon {
            color: #f44336;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .content {
                padding: 20px;
            }

            button {
                font-size: 14px;
            }

            .navbar a {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="admin-panel.php">Back to Admin Panel</a>
    </div>

    <div class="content">
        <h2>Add Subjects</h2>
        <form action="add-subject.php" method="POST">
            <div class="form-group">
                <label>Branch:</label>
                <select name="branch" required>
                    <option value="cs">Computer Science</option>
                    <option value="ec">Electronics</option>
                    <option value="me">Mechanical</option>
                    <option value="ce">Civil</option>
                    <!-- Add more branches as needed -->
                </select>
            </div>

            <div class="subject-form-container" id="subject-form-container">
                <div class="subject-form" id="subject-form-1">
                    <div class="form-group">
                        <label>Subject Name:</label>
                        <input type="text" name="subjects[0][subject_name]" required oninput="toggleIcon(0)">
                    </div>
                    <div class="form-group">
                        <label>Subject Code:</label>
                        <input type="text" name="subjects[0][subject_code]" required oninput="toggleIcon(0)">
                    </div>
                    <span class="plus-icon" onclick="addSubjectForm()" id="icon-0">+</span>
                </div>
            </div>

            <button type="submit">Add Subjects</button>
        </form>
    </div>

    <script>
        let subjectCount = 1; // Start counting from 1 for the second subject
        const maxSubjects = 10; // Limit to 10 subjects

        // Function to add a new subject form dynamically
        function addSubjectForm() {
            if (subjectCount < maxSubjects) {
                // Create new subject form
                const newSubjectForm = document.createElement('div');
                newSubjectForm.classList.add('subject-form');
                newSubjectForm.id = 'subject-form-' + subjectCount;

                newSubjectForm.innerHTML = `
                    <div class="form-group">
                        <label>Subject Name:</label>
                        <input type="text" name="subjects[${subjectCount}][subject_name]" required oninput="toggleIcon(${subjectCount})">
                    </div>
                    <div class="form-group">
                        <label>Subject Code:</label>
                        <input type="text" name="subjects[${subjectCount}][subject_code]" required oninput="toggleIcon(${subjectCount})">
                    </div>
                    <span class="plus-icon" onclick="addSubjectForm()" id="icon-${subjectCount}">+</span>
                    <span class="remove-icon" onclick="removeSubjectForm(${subjectCount})">-</span>
                `;
                
                // Append the new subject form to the container
                document.getElementById('subject-form-container').appendChild(newSubjectForm);
                subjectCount++;
            }
        }

        // Function to remove a subject form
        function removeSubjectForm(subjectId) {
            if (subjectCount > 1) {
                const subjectForm = document.getElementById('subject-form-' + subjectId);
                subjectForm.remove();
                subjectCount--;
            }
        }

        // Toggle icon between + and - based on input
        function toggleIcon(subjectId) {
            const subjectNameInput = document.querySelector(`[name="subjects[${subjectId}][subject_name]"]`);
            const subjectCodeInput = document.querySelector(`[name="subjects[${subjectId}][subject_code]"]`);
            const icon = document.getElementById('icon-' + subjectId);

            if (subjectNameInput.value.trim() !== "" && subjectCodeInput.value.trim() !== "") {
                icon.textContent = "-"; // Change icon to - when both fields are filled
            } else {
                icon.textContent = "+"; // Reset icon to + when fields are empty
            }
        }
    </script>
</body>
</html>
