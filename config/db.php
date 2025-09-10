<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$host = "localhost";      // Usually 'localhost' on most hosting
$user = "root";           // Your MySQL username
$pass = "";               // Your MySQL password
$db   = "memory_book";    // Our database name

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
