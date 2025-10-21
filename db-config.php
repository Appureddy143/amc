<?php
// db-config.php

// Details parsed from your Neon PostgreSQL connection string:
// postgresql://neondb_owner:npg_yC76ISxjPlBh@ep-wandering-hall-a4dwdmsw-pooler.us-east-1.aws.neon.tech/neondb?sslmode=require

$host = "ep-wandering-hall-a4dwdmsw-pooler.us-east-1.aws.neon.tech";
$port = "5432"; // Default PostgreSQL port
$database = "neondb";
$username = "neondb_owner";
$password = "npg_yC76ISxjPlBh";
$sslmode = "require"; // Required for Neon

// Create the DSN (Data Source Name) for PDO
// This tells PDO which driver to use (pgsql) and the connection details.
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
