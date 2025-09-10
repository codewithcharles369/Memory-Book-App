<?php
include '../config/db.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: ../memories.php");
    exit;
}

// Validate user ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Default password (hashed)
    $defaultPassword = password_hash("password123", PASSWORD_DEFAULT);

    // Don’t allow resetting admins
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
    $stmt->bind_param("si", $defaultPassword, $id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header("Location: users.php?msg=reset");
    } else {
        header("Location: users.php?err=cannotreset");
    }
    exit;
} else {
    header("Location: users.php?err=invalid");
    exit;
}
