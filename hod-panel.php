<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

// --- Security Check: Ensure only HODs can access ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hod') {
    // Redirect to login if not an HOD
    header("Location: login.php");
    exit;
}

// --- Get HOD's Branch from Session ---
// Ensure the branch is set during login for HOD users
if (!isset($_SESSION['branch']) || empty($_SESSION['branch'])) {
    // Handle cases where branch might not be set in the session
    die("Error: Branch information is missing for your HOD account. Please contact admin.");
}
$hod_branch = $_SESSION['branch'];

$staff_list = []; // Initialize staff list array

try {
    // --- Fetch staff members from the same branch using PDO ---
    $sql = "SELECT id, first_name, surname, email, subject_code 
            FROM users 
            WHERE role = 'staff' AND branch = ? 
            ORDER BY first_name, surname";
            
    $stmt = $conn->prepare($sql);
    
    // Execute the query with the HOD's branch
    $stmt->execute([$hod_branch]);
    
    // Fetch all matching staff members
    $staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle potential database errors gracefully
    die("Database error: Could not retrieve staff list. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard</title>
    <style>
        /* Consistent Styling */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; transition: background-color 0.3s ease; white-space: nowrap; }
        .navbar a:hover { background-color: #0056b3; }
        .container { width: 90%; max-width: 1000px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2, h3 { color: #444; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        th, td { border: 1px solid #ddd; padding: 0.75em; text-align: left; }
        th { background-color: #007bff; color: #fff; }
        .action-link { 
            text-decoration: none; 
            color: #007bff; 
            padding: 3px 8px; 
            border: 1px solid #007bff; 
            border-radius: 4px; 
            transition: background-color 0.3s, color 0.3s;
        }
        .action-link:hover { 
            background-color: #007bff; 
            color: #fff;
        }
        .no-records { text-align: center; color: #777; padding: 15px; font-style: italic; }
         /* Responsive Table (Optional) */
         @media screen and (max-width: 600px) {
             table, thead, tbody, th, td, tr { display: block; }
             thead tr { position: absolute; top: -9999px; left: -9999px; }
             tr { border: 1px solid #ccc; margin-bottom: 5px; }
             td { border: none; border-bottom: 1px solid #eee; position: relative; padding-left: 50%; white-space: normal; text-align:right; }
             td:before { position: absolute; top: 6px; left: 6px; width: 45%; padding-right: 10px; white-space: nowrap; text-align:left; font-weight: bold; }
             /* Label the data */
             td:nth-of-type(1):before { content: "Name"; }
             td:nth-of-type(2):before { content: "Email"; }
             td:nth-of-type(3):before { content: "Subject Code"; }
             td:nth-of-type(4):before { content: "Action"; }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="generate-paper.php">Create Question Paper</a>
        <!-- Add other relevant links for HOD -->
        <!-- <a href="create-timetable.php">Create Timetables</a> -->
        <!-- <a href="approve-papers.php">Approve Papers</a> -->
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <h2>HOD Dashboard (Branch: <?= htmlspecialchars($hod_branch) ?>)</h2>
        <p>Manage staff and question papers for your department.</p>

        <h3>Staff Members in Your Branch</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject Code (Primary)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staff_list)): ?>
                    <tr>
                        <td colspan="4" class="no-records">No staff members found in your branch.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($staff_list as $staff): ?>
                        <tr>
                            <td><?= htmlspecialchars($staff['first_name'] . " " . $staff['surname']) ?></td>
                            <td><?= htmlspecialchars($staff['email']) ?></td>
                            <td><?= isset($staff['subject_code']) ? htmlspecialchars($staff['subject_code']) : 'N/A' ?></td>
                            <td>
                                <!-- Link to a potential detail view page (you'll need to create this) -->
                                <a href='view-staff-details.php?id=<?= $staff['id'] ?>' class="action-link">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
