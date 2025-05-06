<?php
require('fpdf.php');

// Database connection
$mysqli = new mysqli("localhost", "username", "password", "database_name");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Retrieve semester and year from GET request
$semester = $_GET['semester']; // Retrieve semester from URL
$year = $_GET['year']; // Retrieve year from URL

// Query to get the timetable for the selected semester and year
$sql = "SELECT subject, day, time FROM timetable WHERE semester = ? AND year = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $semester, $year);
$stmt->execute();
$result = $stmt->get_result();

// Create a new PDF instance
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Title of the PDF
$pdf->Cell(0, 10, 'Timetable for Semester ' . $semester . ' - ' . $year, 0, 1, 'C');

// Table header
$pdf->Cell(40, 10, 'Subject', 1);
$pdf->Cell(40, 10, 'Day', 1);
$pdf->Cell(40, 10, 'Time', 1);
$pdf->Ln();

// Output timetable rows from database
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(40, 10, $row['subject'], 1);
    $pdf->Cell(40, 10, $row['day'], 1);
    $pdf->Cell(40, 10, $row['time'], 1);
    $pdf->Ln();
}

// Output the PDF
$pdf->Output();
?>
