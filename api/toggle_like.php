<?php
include '../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$memory_id = intval($_POST['memory_id'] ?? 0);

if (!$memory_id) {
    echo json_encode(["success" => false, "error" => "Invalid memory"]);
    exit;
}

// Check if liked
$stmt = $conn->prepare("SELECT id FROM likes WHERE memory_id = ? AND user_id = ?");
$stmt->bind_param("ii", $memory_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Unlike
    $conn->query("DELETE FROM likes WHERE memory_id = $memory_id AND user_id = $user_id");
    $liked = false;
} else {
    // Like
    $stmt = $conn->prepare("INSERT INTO likes (memory_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $memory_id, $user_id);
    $stmt->execute();
    $liked = true;
}

// Return updated like count
$count = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE memory_id = ?");
$count->bind_param("i", $memory_id);
$count->execute();
$total = $count->get_result()->fetch_assoc()['total'];

echo json_encode([
    "success" => true,
    "liked" => $liked,
    "total" => $total
]);
