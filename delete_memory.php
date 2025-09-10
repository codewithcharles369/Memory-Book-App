<?php
include 'config/db.php';
session_start();

// Ensure user is logged in
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

// Fetch memory
$stmt = $conn->prepare("SELECT * FROM memories WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $memory_id, $user_id);
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
$stmt = $conn->prepare("DELETE FROM memories WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $memory_id, $user_id);
$stmt->execute();

header("Location: memories.php?msg=deleted");
exit;
