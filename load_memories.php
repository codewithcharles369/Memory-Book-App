<?php
include 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if current user is admin
$is_admin = false;
$stmtRole = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmtRole->bind_param("i", $user_id);
$stmtRole->execute();
$resRole = $stmtRole->get_result();
if ($resRole && $r = $resRole->fetch_assoc()) {
    $is_admin = ($r['role'] === 'admin');
}

$offset = intval($_GET['offset'] ?? 0);
$limit = 12;

// Filters
$search = $_GET['q'] ?? '';
$searchLike = '%' . $search . '%';
$sort = $_GET['sort'] ?? 'latest';
$orderBy = "m.created_at DESC";
$extra = "";
$joinLikes = "";

if ($sort === "liked") {
    $orderBy = "like_count DESC, m.created_at DESC";
} elseif ($sort === "mine") {
    $extra = "AND m.user_id = ?";
} elseif ($sort === "favorites") {
    $joinLikes = "JOIN likes l ON m.id = l.memory_id";
    $extra = "AND l.user_id = ?";
} elseif ($sort === "private" && $is_admin) {
    $extra = "AND m.privacy = 'private'";
}

$wherePrivacy = $is_admin ? "1" : "(m.privacy = 'public' OR m.user_id = ?)";

$sql = "
    SELECT m.*, u.display_name,
           (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
    FROM memories m
    JOIN users u ON m.user_id = u.id
    $joinLikes
    WHERE $wherePrivacy
    $extra
";

if (!empty($search)) {
    $sql .= " AND (m.title LIKE ? OR m.description LIKE ? OR m.tags LIKE ?)";
}

$sql .= " ORDER BY $orderBy LIMIT ?, ?";

$stmt = $conn->prepare($sql);

// Build params
$params = [];
$types = "";

if (!$is_admin) { $params[] = $user_id; $types .= "i"; }
if ($sort === "mine" || $sort === "favorites") { $params[] = $user_id; $types .= "i"; }
if (!empty($search)) {
    $params[] = $searchLike; $params[] = $searchLike; $params[] = $searchLike; $types .= "sss";
}
$params[] = $offset; $params[] = $limit; $types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$memories = [];
while ($row = $res->fetch_assoc()) {
    $memories[] = $row;
}

header('Content-Type: application/json');
echo json_encode($memories);
