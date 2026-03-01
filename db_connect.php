<?php
/**
 * Database Connection Configuration
 * Mezzanine Restaurant Website
 */

// Database credentials
define('DB_HOST', 'localhost');     // Usually 'localhost'
define('DB_USER', 'root'); // Your MySQL username
define('DB_PASS', ''); // Your MySQL password
define('DB_NAME', 'mezzanine_restaurant');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Optional: Set timezone
date_default_timezone_set('Asia/Manila'); // Change to your timezone

?>
