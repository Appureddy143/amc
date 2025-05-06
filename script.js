require('fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Question Paper', 0, 1, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Fetch the content from the database
$conn = new mysqli($servername, $username, $password, $dbname);
$result = $conn->query("SELECT title, content FROM question_papers WHERE id = LAST_INSERT_ID()");

if ($row = $result->fetch_assoc()) {
    $pdf->Cell(0, 10, $row['title'], 0, 1);
    $pdf->MultiCell(0, 10, $row['content']);
}

$pdf->Output('D', 'question_paper.pdf');