<?php
include 'config/db.php';

// Ensure admin only
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Not logged in');
}

$user_id = $_SESSION['user_id'];

// Check role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row || $row['role'] !== 'admin') {
    http_response_code(403);
    exit('Not authorized');
}

// Get data
$memory_id = $_POST['memory_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$memory_id || !in_array($action, ['approve','reject'])) {
    http_response_code(400);
    exit('Invalid input');
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';

$update = $conn->prepare("UPDATE memories SET status = ? WHERE id = ?");
$update->bind_param("si", $newStatus, $memory_id);
$update->execute();

echo "success";
