<?php
session_start();
include('db-config.php'); // Include database connection (though not used for queries here)

// Check if the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Store selected semester and year in session and redirect
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['go'])) {
    // Validate inputs
    $semester = filter_input(INPUT_POST, 'semester', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_SPECIAL_CHARS); // Sanitize year string

    if ($semester && $year) {
        $_SESSION['selectedSemester'] = $semester;
        $_SESSION['selectedYear'] = $year;
        // Redirect to the next step where the actual form is built
        header("Location: create-timetable-form.php"); 
        exit;
    } else {
        // Handle invalid input if necessary
        $error_message = "Please select both semester and year.";
    }
}

// --- Generate options for dropdowns ---
$semesters = range(1, 8); // Semesters 1 to 8
$current_year = date("Y");
$years = [];
// Generate academic years like 2023-2024 for the next 5 years
for ($y = $current_year - 1; $y <= $current_year + 4; $y++) {
    $years[] = "$y-" . ($y + 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Semester and Year</title>
    <style>
        /* Consistent Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column; /* Stack navbar and content */
            align-items: center;
            min-height: 100vh;
        }
        .navbar {
            background-color: #007bff;
            padding: 1em;
            display: flex;
            justify-content: flex-end; /* Align links to the right */
            gap: 1em;
            width: 100%; /* Full width */
            box-sizing: border-box;
        }
        .navbar a {
            color: #fff;
            text-decoration: none;
            padding: 0.5em 1em;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .navbar a:hover {
            background-color: #0056b3;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px; /* Suitable width for the form */
            margin-top: 40px; /* Space below navbar */
            text-align: center;
        }
        h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 25px;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center; /* Center form elements */
        }
        label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            text-align: left; /* Align labels left */
            width: 100%; /* Make labels take full width */
        }
        select {
            padding: 10px;
            margin-bottom: 20px; /* Increased space */
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%; /* Full width */
            box-sizing: border-box; 
        }
        button[type="submit"] {
            padding: 12px 30px; /* More padding */
            background-color: #28a745; /* Green color */
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            width: auto; /* Allow button to size naturally */
        }
        button[type="submit"]:hover {
            background-color: #218838;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="container">
        <h2>Select Timetable Details</h2>
        
        <?php if (isset($error_message)) {
            echo "<p class='error-message'>" . htmlspecialchars($error_message) . "</p>";
        } ?>

        <!-- Form posts to itself, PHP handles redirect -->
        <form action="create-timetable.php" method="POST"> 
            <label for="semester">Select Semester:</label>
            <select name="semester" id="semester" required>
                <option value="" disabled selected>-- Select Semester --</option>
                <?php foreach ($semesters as $sem) { ?>
                    <option value="<?= $sem ?>">Semester <?= $sem ?></option>
                <?php } ?>
            </select>

            <label for="year">Select Academic Year:</label>
            <select name="year" id="year" required>
                <option value="" disabled selected>-- Select Year --</option>
                <?php foreach ($years as $year) { ?>
                    <option value="<?= $year ?>">Year <?= $year ?></option>
                <?php } ?>
            </select>
            
            <button type="submit" name="go">Proceed</button>
        </form>
    </div>
</body>
</html>
