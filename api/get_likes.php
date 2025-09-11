<?php
include '../config/db.php';
session_start();

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Validate input
if (empty($_POST['memory_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing memory_id']);
    exit;
}

$memory_id = intval($_POST['memory_id']);

// Fetch users who liked this memory
$stmt = $conn->prepare("
    SELECT u.display_name
    FROM likes l
    JOIN users u ON l.user_id = u.id
    WHERE l.memory_id = ?
    ORDER BY u.display_name ASC
");
$stmt->bind_param("i", $memory_id);
$stmt->execute();
$res = $stmt->get_result();

$friends = [];
while ($row = $res->fetch_assoc()) {
    $friends[] = ['name' => $row['display_name']];
}

echo json_encode([
    'success' => true,
    'friends' => $friends
]);
