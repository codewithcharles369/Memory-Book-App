<?php
include 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: memories.php");
    exit;
}

$memory_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Check if current user is admin
$is_admin = false;
$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleRes = $roleStmt->get_result();
if ($roleRes && $roleRow = $roleRes->fetch_assoc()) {
    $is_admin = ($roleRow['role'] === 'admin');
}

// Fetch memory (owner OR admin)
if ($is_admin) {
    $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ?");
    $stmt->bind_param("i", $memory_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $memory_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header("Location: memories.php?err=notfound");
    exit;
}

$memory = $result->fetch_assoc();

// Delete image file if exists
if (!empty($memory['image_path']) && file_exists($memory['image_path'])) {
    unlink($memory['image_path']);
}

// Delete memory
if ($is_admin) {
    $del = $conn->prepare("DELETE FROM memories WHERE id = ?");
    $del->bind_param("i", $memory_id);
} else {
    $del = $conn->prepare("DELETE FROM memories WHERE id = ? AND user_id = ?");
    $del->bind_param("ii", $memory_id, $user_id);
}
$del->execute();

header("Location: memories.php?msg=deleted");
exit;
