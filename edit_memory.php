<?php
include 'includes/header.php';
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$memory_id = intval($_GET['id'] ?? 0);
$user_id   = $_SESSION['user_id'];

// Fetch memory (owner OR admin)
if (isAdmin()) {
    $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ?");
    $stmt->bind_param("i", $memory_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM memories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $memory_id, $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-center text-gray-600'>You don’t have permission to edit this memory 💔.</p>";
    include 'includes/footer.php';
    exit;
}

$memory = $result->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $tags        = trim($_POST['tags']);
    $privacy     = $_POST['privacy'];

    $media_path  = $memory['media_path'];  // keep existing
    $media_type  = $memory['media_type'];

    if (!empty($_FILES['media']['name'])) {
        // Check size
        if ($_FILES['media']['size'] > 10 * 1024 * 1024) {
            $err = "File too large. Max size is 10MB.";
        } else {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            $isVideo = in_array($ext, ['mp4','mov','webm']);
            $newPath = $uploadDir . time() . "_" . basename($_FILES['media']['name']);

            if (move_uploaded_file($_FILES['media']['tmp_name'], $newPath)) {
                // Delete old file
                if ($media_path && file_exists($media_path)) unlink($media_path);
                $media_path = $newPath;
                $media_type = $isVideo ? 'video' : 'image';
            }
        }
    }

    if (isAdmin() && $memory['user_id'] != $user_id) {
        $lastEditedBy = "Destiny";
    } else {
        $lastEditedBy = null;
    }

    if (empty($err)) {
        if (isAdmin()) {
            $stmt = $conn->prepare("UPDATE memories 
                SET title=?, description=?, media_path=?, media_type=?, tags=?, privacy=?, last_edited_by=?
                WHERE id=?");
            $stmt->bind_param("sssssssi", $title, $description, $media_path, $media_type, $tags, $privacy, $lastEditedBy, $memory_id);
        } else {
            $stmt = $conn->prepare("UPDATE memories 
                SET title=?, description=?, media_path=?, media_type=?, tags=?, privacy=?, last_edited_by=?
                WHERE id=? AND user_id=?");
            $stmt->bind_param("sssssssii", $title, $description, $media_path, $media_type, $tags, $privacy, $lastEditedBy, $memory_id, $user_id);
        }

        if ($stmt->execute()) {
            header("Location: memories.php?msg=updated");
            exit;
        } else {
            $err = "Something went wrong while updating 💔.";
        }
    }
}
?>


<div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl shadow">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6">Edit Memory ✏️</h1>

  <?php if (!empty($err)): ?>
    <p class="bg-red-100 text-red-600 p-2 rounded mb-4"><?php echo $err; ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-4">
    <div>
      <label class="block mb-1 font-medium">Title</label>
      <input type="text" name="title" value="<?php echo htmlspecialchars($memory['title']); ?>" class="w-full border rounded-lg p-2" required>
    </div>

    <div>
      <label class="block mb-1 font-medium">Description</label>
      <textarea name="description" rows="4" class="w-full border rounded-lg p-2" required><?php echo htmlspecialchars($memory['description']); ?></textarea>
    </div>

    <div>
      <label class="block mb-1 font-medium">Current Media</label>
      <?php if ($memory['media_type'] === 'video'): ?>
        <video src="<?php echo htmlspecialchars($memory['media_path']); ?>" controls class="w-40 h-40 object-cover rounded mb-2"></video>
      <?php elseif ($memory['media_type'] === 'image'): ?>
        <img src="<?php echo htmlspecialchars($memory['media_path']); ?>" class="w-40 h-40 object-cover rounded mb-2">
      <?php endif; ?>
      
      <label class="block mb-1 font-medium mt-2">Replace Media (Image or Video < 10MB)</label>
      <input type="file" name="media" accept="image/*,video/*" class="w-full">
    </div>

    <div>
      <label class="block mb-1 font-medium">Tags</label>
      <input type="text" name="tags" value="<?php echo htmlspecialchars($memory['tags']); ?>" class="w-full border rounded-lg p-2">
    </div>

    <div>
      <label class="block mb-1 font-medium">Privacy</label>
      <select name="privacy" class="w-full border rounded-lg p-2">
        <option value="public" <?php if ($memory['privacy'] === 'public') echo 'selected'; ?>>Public 🌍</option>
        <option value="private" <?php if ($memory['privacy'] === 'private') echo 'selected'; ?>>Private 🔒</option>
      </select>
    </div>

    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded-xl hover:bg-pink-700 transition">
      Update Memory
    </button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>