<?php
// api/get_likes.php
header('Content-Type: application/json; charset=utf-8');

// load DB (adjust path if your structure differs)
include_once __DIR__ . '/../config/db.php';

// start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// quick debug toggle (development only)
// ini_set('display_errors', 1); error_reporting(E_ALL);

// require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

// memory id from POST
$memory_id = isset($_POST['memory_id']) ? intval($_POST['memory_id']) : 0;
if ($memory_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_memory_id']);
    exit;
}

// prepare statement
$sql = "
    SELECT u.display_name
    FROM likes l
    JOIN users u ON l.user_id = u.id
    WHERE l.memory_id = 
    ORDER BY l.created_at DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    // return SQL error for debugging
    echo json_encode(['success' => false, 'error' => 'prepare_failed', 'sql_error' => $conn->error]);
    exit;
}

$stmt->bind_param("i", $memory_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'execute_failed', 'sql_error' => $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$friends = [];
while ($row = $result->fetch_assoc()) {
    // return an object per friend (you can add id/avatar later)
    $friends[] = [
        'name' => $row['display_name']
    ];
}

$stmt->close();

echo json_encode(['success' => true, 'friends' => $friends], JSON_UNESCAPED_UNICODE);
exit;
