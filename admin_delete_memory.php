<?php
include 'config/db.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: memories.php");
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $memory_id = intval($_GET['id']);

    // First fetch the memory to get image path (if any)
    $stmt = $conn->prepare("SELECT image_path FROM memories WHERE id = ?");
    $stmt->bind_param("i", $memory_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $memory = $result->fetch_assoc();

    if ($memory) {
        // Delete the memory
        $delete_stmt = $conn->prepare("DELETE FROM memories WHERE id = ?");
        $delete_stmt->bind_param("i", $memory_id);

        if ($delete_stmt->execute()) {
            // Remove image file if it exists
            if (!empty($memory['image_path']) && file_exists("" . $memory['image_path'])) {
                unlink("" . $memory['image_path']);
            }

            header("Location: admin_memories.php?msg=deleted");
            exit;
        } else {
            header("Location: admin_memories.php?err=invalid");
            exit;
        }
    } else {
        header("Location: admin_memories.php?err=invalid");
        exit;
    }
} else {
    header("Location: admin_memories.php?err=invalid");
    exit;
}
