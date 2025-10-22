<?php
session_start();
// 1. Include the correct PDO database connection
include('db-config.php'); 

// Ensure only principals can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'principal') {
    header("Location: login.php");
    exit;
}

$users = []; // Initialize users array
try {
    // 2. Prepare the query using PDO
    $sql = "SELECT id, first_name, surname, email, role FROM users WHERE role IN ('staff', 'hod') ORDER BY role, first_name";
    $stmt = $conn->prepare($sql);
    
    // 3. Execute the query
    $stmt->execute();
    
    // 4. Fetch all results
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors gracefully
    die("Database error: Could not retrieve user list. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
    <style>
        /* Consistent Styling */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        .navbar { background-color: #007bff; padding: 1em; display: flex; justify-content: flex-end; gap: 1em; }
        .navbar a { color: #fff; text-decoration: none; padding: 0.5em 1em; border-radius: 5px; transition: background-color 0.3s ease; }
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
        .no-records { text-align: center; color: #777; padding: 15px; }
        /* Add other styles from your styles.css or previous files if needed */
    </style>
</head>
<body>
    <div class="navbar">
        <a href="logout.php">Logout</a>
        <!-- Add other relevant links for the principal -->
        <!-- <a href="manage-users.php">Manage Users</a> -->
        <!-- <a href="view-reports.php">View Reports</a> -->
    </div>
    
    <div class="container">
        <h2>Principal Dashboard</h2>
        <p>Overview of staff and Heads of Department.</p>

        <h3>Staff & HOD Members</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="4" class="no-records">No staff or HOD members found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['surname']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role'])) // Capitalize role ?></td>
                            <td>
                                <!-- Link to a potential detail view page -->
                                <a href="view-user-details.php?id=<?= $user['id'] ?>" class="action-link">View Details</a> 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>