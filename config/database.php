<?php
// Database configuration
$host = 'localhost';  // Your database host (e.g., 'localhost')
$dbname = 'project_manager';  // Replace with your database name
$username = 'root';  // Replace with your database username
$password = '';  // Replace with your database password

try {
    // Create a PDO instance (database connection)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set the default fetch mode to associative arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // You can optionally log a successful connection
    // error_log("Connected to the database successfully!");
} catch (PDOException $e) {
    // Handle connection errors
    error_log("Connection failed: " . $e->getMessage());
    // Optionally you can stop script execution on connection failure
    die("Could not connect to the database.");
}
?>
