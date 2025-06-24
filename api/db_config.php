<?php
// Enable detailed error reporting for debugging.
// IMPORTANT: In a production environment, you should DISABLE these lines
// by commenting them out or removing them, as they can expose sensitive information.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This file contains the database connection parameters.
// It's kept separate for organization and security.

// Define database credentials
// IMPORTANT: For production, do NOT hardcode sensitive credentials like this.
// Use environment variables or a more secure configuration method.
define('DB_SERVER', 'localhost'); // Your database server, often 'localhost'
define('DB_USERNAME', 'root');    // Your database username (e.g., 'root' for XAMPP default)
define('DB_PASSWORD', '');        // Your database password (empty for XAMPP default)
define('DB_NAME', 'expense_tracker_db'); // The name of your database

// Attempt to connect to MySQL database, specifying the port 3307
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, 3307);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop script execution and display an error.
    // In a real application, you might log this error and show a generic message to the user.
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8 for proper handling of various characters
$conn->set_charset("utf8");
?>
