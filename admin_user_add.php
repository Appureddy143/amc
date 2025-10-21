<?php
// 1. Include your new database configuration file
// This file creates the $conn variable using PDO
require_once 'db-config.php';

// Admin details
$staff_id = 'S001';
$first_name = 'Admin';
$surname = 'User';
$dob = '2000-01-01';
$address = '123 Admin St, City';
$email = 'admin@example.com';
$password = 'admin123'; // Plain password

// Hash the password using PHP's password_hash function
// This part is correct and does not need to change
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 2. Use a try...catch block for error handling (best practice with PDO)
try {
    // 3. Create the SQL query with placeholders (?)
    // We put 'admin' and NOW() directly in the query as they are static values
    $query = "INSERT INTO users (staff_id, first_name, surname, dob, address, email, password, role, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', NOW())";

    // 4. Prepare the statement
    $stmt = $conn->prepare($query);
    
    // 5. Execute the statement, passing the variables in an array
    // The order of variables must match the order of the placeholders (?)
    $stmt->execute([
        $staff_id,
        $first_name,
        $surname,
        $dob,
        $address,
        $email,
        $hashed_password
    ]);

    echo "Admin user added successfully!";

} catch (PDOException $e) {
    // This will catch any database errors
    echo "Error: " . $e->getMessage();
}

// 6. Close the connection
$conn = null;
?>
