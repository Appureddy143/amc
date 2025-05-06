<?php 
session_start(); 
include('db-config.php'); // Database connection

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); 
    exit; 
} 

// Retrieve selected semester and year from session
$selectedSemester = $_SESSION['selectedSemester'] ?? ''; 
$selectedYear = $_SESSION['selectedYear'] ?? ''; 

// If semester or year is not selected, redirect to create timetable page
if (empty($selectedSemester) || empty($selectedYear)) { 
    header("Location: create-timetable.php"); 
    exit; 
}

// Handle custom subject addition if provided through the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_subject_name']) && isset($_POST['custom_subject_type'])) {
    $subject_name = $_POST['custom_subject_name'] ?? '';
    $subject_type = $_POST['custom_subject_type'] ?? '';

    if (!empty($subject_name)) {
        // Insert custom subject into the database (no subject code)
        $stmt = $conn->prepare("INSERT INTO subjects (name, semester, year, subject_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $subject_name, $selectedSemester, $selectedYear, $subject_type);
        $stmt->execute();
        echo "<p style='color: green;'>Custom subject added successfully!</p>";
    }
}

// Fetch subjects for selected semester and year
$subjectsQuery = "SELECT id, name FROM subjects WHERE semester = ? AND year = ?";
$subjectsStmt = $conn->prepare($subjectsQuery);
$subjectsStmt->bind_param("ss", $selectedSemester, $selectedYear);
$subjectsStmt->execute();
$subjectsResult = $subjectsStmt->get_result();

// Fetch staff details
$staffQuery = "SELECT id, first_name, surname FROM users WHERE role = 'staff'";
$staffResult = $conn->query($staffQuery);

// Handle timetable submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule'])) {
    foreach ($_POST['schedule'] as $day => $periods) {
        foreach ($periods as $period => $data) {
            $subject_id = $data['subject'] ?? ''; 
            $staff_id = $data['staff'] ?? ''; 
            $start_time = $data['start_time'] ?? ''; 
            $end_time = $data['end_time'] ?? '';
            $merge_periods = isset($data['merge']) ? 1 : 0;  // Check if merge is selected

            if (!empty($subject_id) && !empty($staff_id) && !empty($start_time) && !empty($end_time)) {
                // Insert into timetable with an optional merge option
                $stmt = $conn->prepare(
                    "INSERT INTO timetable (day, period, subject_id, staff_id, start_time, end_time, semester, year, merge_periods) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param("siiissssi", $day, $period, $subject_id, $staff_id, $start_time, $end_time, $selectedSemester, $selectedYear, $merge_periods);
                $stmt->execute();
            }
        }
    }
    echo "<p style='color: green;'>Timetable successfully created!</p>";
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Timetable</title>
    <style>
        /* General Page Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background-color: #333;
            padding: 10px;
            text-align: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 18px;
        }

        .content {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            font-size: 14px;
        }

        table th {
            background-color: #f7f7f7;
            font-weight: bold;
        }

        table td input[type="time"] {
            width: 100px;
            padding: 3px;
        }

        table td select {
            width: 100px;
            padding: 5px;
            margin-top: 5px;
        }

        /* Break and Merge Options Styling */
        table td input[type="checkbox"] {
            margin-top: 10px;
        }

        #customSubjectFields {
            margin-top: 10px;
        }

        #customSubjectFields input {
            width: 100px;
            padding: 5px;
            margin-top: 5px;
        }

        /* Button Styling */
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            border-radius: 5px;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Heading Styles */
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        h3 {
            color: #333;
            margin-bottom: 10px;
        }

        /* Input and Form Styling */
        form {
            display: block;
            margin-top: 20px;
        }

        form input, form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        form input[type="submit"], form button {
            width: auto;
            margin-top: 20px;
        }
    </style>
    <script>
        // JavaScript function to show or hide the custom subject input fields
        function toggleCustomSubjectFields(selectElement) {
            var customSubjectFields = document.getElementById('customSubjectFields');
            if (selectElement.value === "custom") {
                customSubjectFields.style.display = "block";  // Show custom subject input fields
            } else {
                customSubjectFields.style.display = "none";   // Hide custom subject input fields
            }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <a href="create-timetable.php">Back</a>
    </div>

    <div class="content">
        <h2>Create Weekly Timetable - Semester <?= $selectedSemester ?> (<?= $selectedYear ?>)</h2>
        
        <!-- Timetable form -->
        <form action="create-timetable-form.php" method="POST">
            <h3>Timetable for Semester <?= $selectedSemester ?> (<?= $selectedYear ?>)</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>Day</th>
                        <?php for ($i = 1; $i <= 9; $i++) echo "<th>Period $i</th>"; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="10" style="text-align: center; font-weight: bold;">Tea Break (11:00 - 11:15)</td>
                    </tr>
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; 
                    foreach ($days as $day): 
                    ?>
                        <tr>
                            <td><?= $day ?></td>
                            <?php $periodNumber = 1; ?>
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                                <?php if ($i == 3) { 
                                    // Tea Break after 2nd period
                                    echo "<td>Tea Break (11:00 - 11:15)</td>";
                                    $periodNumber++;
                                    continue; 
                                } elseif ($i == 6) {
                                    // Lunch Break after 4th period
                                    echo "<td>Lunch Break (1:15 - 2:00)</td>";
                                    $periodNumber++;
                                    continue;
                                } ?>
                                <td>
                                    <input type="time" name="schedule[<?= $day ?>][<?= $periodNumber ?>][start_time]" required> to 
                                    <input type="time" name="schedule[<?= $day ?>][<?= $periodNumber ?>][end_time]" required><br>

                                    <select name="schedule[<?= $day ?>][<?= $periodNumber ?>][subject]" required onchange="toggleCustomSubjectFields(this)">
                                        <option value="">Select Subject</option>
                                        <option value="custom">Add Custom Subject</option> <!-- Option to add custom subject -->
                                        <?php 
                                        $subjectsResult->data_seek(0);
                                        while ($row = $subjectsResult->fetch_assoc()):
                                        ?>
                                            <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select><br>

                                    <!-- Custom subject fields (only shown when 'Add Custom Subject' is selected) -->
                                    <div id="customSubjectFields" style="display:none;">
                                        <input type="text" name="custom_subject_name" placeholder="Custom Subject Name" required><br>
                                        <input type="hidden" name="custom_subject_type" value="lab_practical"> <!-- Indicating it's for a lab practical -->
                                    </div>

                                    <select name="schedule[<?= $day ?>][<?= $periodNumber ?>][staff]" required>
                                        <option value="">Select Staff</option>
                                        <?php 
                                        $staffResult->data_seek(0);
                                        while ($row = $staffResult->fetch_assoc()):
                                        ?>
                                            <option value="<?= $row['id'] ?>"><?= $row['first_name'] ?> <?= $row['surname'] ?></option>
                                        <?php endwhile; ?>
                                    </select><br>

                                    <!-- Option to merge periods -->
                                    <label for="merge_periods">Merge Periods:</label>
                                    <input type="checkbox" name="schedule[<?= $day ?>][<?= $periodNumber ?>][merge]"><br>
                                </td>
                                <?php $periodNumber++; ?>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit">Save Timetable</button>
        </form>
    </div>
</body>
</html>
