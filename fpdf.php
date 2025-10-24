<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 
// 2. Include the Composer autoloader to load FPDF
require 'vendor/autoload.php';

// --- (Optional) Security Check: Ensure only authorized users can access ---
// Example: Allow admin, hod, staff
// $allowed_roles = ['admin', 'hod', 'staff', 'principal']; 
// if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
//     die("Unauthorized access.");
// }

// --- Get Semester and Year from URL ---
// Use filter_input for safer retrieval of GET parameters
$semester = filter_input(INPUT_GET, 'semester', FILTER_VALIDATE_INT);
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);

// Validate inputs
if ($semester === false || $year === false || $semester < 1 || $year < 1) {
    die("Invalid semester or year provided.");
}

$timetable_data = []; // Initialize array for results

try {
    // --- Fetch Timetable Data using PDO ---
    // Assuming 'timetable' table has columns: subject_id, day_of_week, start_time, end_time
    // And 'subjects' table has id, name
    // Adjust the query based on your actual 'timetables' table structure
    $sql = "SELECT s.name as subject_name, tt.day_of_week, tt.start_time, tt.end_time 
            FROM timetables tt
            JOIN subjects s ON tt.subject_id = s.id
            WHERE s.semester = ? AND s.year = ? 
            ORDER BY tt.day_of_week, tt.start_time"; // Example query - MODIFY AS NEEDED

    $stmt = $conn->prepare($sql);
    $stmt->execute([$semester, $year]);
    $timetable_data = $stmt->fetchAll(PDO.FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors
    die("Database Error: Could not retrieve timetable data. " . $e.getMessage());
} catch (Exception $e) {
    // Handle other errors (like FPDF issues)
    die("Error: " . $e.getMessage());
}

// --- Generate PDF using FPDF ---
try {
    $pdf = new FPDF\FPDF(); // Use the FPDF namespace
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14); // Slightly larger title font

    // Title
    $pdf->Cell(0, 10, 'Timetable - Semester ' . $semester . ' / Year ' . $year, 0, 1, 'C');
    $pdf->Ln(5); // Add a little space

    // Table Header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(60, 10, 'Subject', 1, 0, 'C'); // Increased width
    $pdf->Cell(40, 10, 'Day', 1, 0, 'C');
    $pdf->Cell(50, 10, 'Time', 1, 1, 'C'); // Increased width & new line

    // Table Data
    $pdf->SetFont('Arial', '', 10);
    if (empty($timetable_data)) {
        $pdf->Cell(0, 10, 'No timetable data found for this semester/year.', 1, 1, 'C');
    } else {
        foreach ($timetable_data as $row) {
            // Format time nicely if stored as DATETIME or TIME
            $time_display = date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time']));
            
            $pdf->Cell(60, 10, htmlspecialchars($row['subject_name']), 1); // Use htmlspecialchars just in case
            $pdf->Cell(40, 10, htmlspecialchars($row['day_of_week']), 1); 
            $pdf->Cell(50, 10, $time_display, 1); 
            $pdf->Ln(); // Move to the next line
        }
    }

    // Output the PDF
    // D: Force download, I: Output inline in browser
    $pdf->Output('D', 'Timetable_Sem' . $semester . '_Year' . $year . '.pdf'); 
    exit; // Stop script execution after sending PDF

} catch (Exception $e) {
     // Catch FPDF specific errors if any occur during generation
    die("Error generating PDF: " . $e.getMessage());
}

?>
