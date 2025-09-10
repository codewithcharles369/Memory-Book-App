<?php
include 'config/db.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$memory_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id   = $_SESSION['user_id'];

// Check if current user is admin
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Fetch memory
if ($isAdmin) {
    $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ?");
    $stmt->bind_param("i", $memory_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $memory_id, $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: memories.php?err=notfound");
    exit;
}

$memory = $result->fetch_assoc();

// Delete image file if exists
if ($memory['image_path'] && file_exists($memory['image_path'])) {
    unlink($memory['image_path']);
}

// Delete memory
if ($isAdmin) {
    $stmt = $conn->prepare("DELETE FROM memories WHERE id = ?");
    $stmt->bind_param("i", $memory_id);
} else {
    $stmt = $conn->prepare("DELETE FROM memories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $memory_id, $user_id);
}
$stmt->execute();

header("Location: memories.php?msg=deleted");
exit;
