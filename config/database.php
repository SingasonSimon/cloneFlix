<?php
// config/database.php

// --- Database Configuration ---
// Define constants for database credentials.
// Using constants makes it clear these values shouldn't change during script execution.

// Database Host: Usually 'localhost' or '127.0.0.1' for local development with XAMPP.
define('DB_HOST', 'localhost');

// Database Name: The name of the database we created.
define('DB_NAME', 'netflix_clone_db');

// Database User: The default username for XAMPP MySQL/MariaDB is often 'root'.
define('DB_USER', 'root');

// Database Password: The default password for XAMPP MySQL/MariaDB is often empty ('').
// IMPORTANT: For a real application, ALWAYS use a strong, unique password!
define('DB_PASS', '');

// Character Set: Recommended for broad character support.
define('DB_CHARSET', 'utf8mb4');

// --- Establish PDO Connection ---

// Set PDO options
// - PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION: Makes PDO throw exceptions on errors, which we can catch.
// - PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC: Makes fetch() return associative arrays by default (column names as keys).
// - PDO::ATTR_EMULATE_PREPARES => false: Disables emulation of prepared statements for security and compatibility.
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Data Source Name (DSN): String containing the information required to connect to the database.
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Attempt to create a new PDO instance (database connection)
try {
    // Create the PDO object
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Optional: You could uncomment the line below for testing the connection,
    // but remove it for actual use to avoid printing messages everywhere.
    // echo "Database connection successful!";

} catch (\PDOException $e) {
    // If connection fails, catch the exception and display an error message.
    // In a real application, you might log this error instead of showing it directly to the user.
    // Use htmlspecialchars() to prevent potential XSS if the error message contains HTML/JS.
    // Use exit() or die() to stop script execution if the database connection is critical.
    throw new \PDOException("Database connection failed: " . htmlspecialchars($e->getMessage()), (int)$e->getCode());
    // Or a simpler error message for the user:
    // die("Database connection failed. Please try again later or contact support.");
}

// The $pdo variable now holds the database connection object if successful.
// We will include/require this file in other PHP scripts where we need database access.
// Example usage in another file:
// require_once __DIR__ . '/config/database.php';
// $stmt = $pdo->query('SELECT * FROM users');
// ...

?>

