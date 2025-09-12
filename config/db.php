<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = "db.pxxl.pro:4666";      // Usually 'localhost' on most hosting
$user = "user_78bab0bd";           // Your MySQL username
$pass = "713c7b223c050044aa40104ff204ee60";               // Your MySQL password
$db   = "db_70c49042";    // Our database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure proper character encoding
$conn->set_charset("utf8mb4");

// ✅ Safe helper function
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>
