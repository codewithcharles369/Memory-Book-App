<?php
include 'config/db.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: memories.php");
    exit;
}

// Validate user ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Don’t allow deleting admins
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header("Location: admin_users.php?msg=deleted");
    } else {
        header("Location: admin_users.php?err=cannotdelete");
    }
    exit;
} else {
    header("Location: users.php?err=invalid");
    exit;
}
