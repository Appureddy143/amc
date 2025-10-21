<?php
// db-config.php

// Get database details from Render Environment Variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$database = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$sslmode = "require"; // Always require for Neon

// Check if any environment variables are missing
if (empty($host) || empty($port) || empty($database) || empty($username) || empty($password)) {
    die("Connection failed: Database environment variables are not set.");
}

// Create the DSN (Data Source Name) for PDO
$dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=$sslmode";

try {
    // Create the PDO connection instance
    $conn = new PDO($dsn, $username, $password);

    // Set the PDO error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If connection fails, stop the script and show the error
    die("Connection failed: " . $e->getMessage());
}

// The $conn variable now holds your active database connection.
?>
