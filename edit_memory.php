<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
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

    $image_path = $memory['image_path']; // keep old image unless replaced

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Replace with new image
        $new_image_path = $uploadDir . time() . "_img_" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $new_image_path)) {
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            $image_path = $new_image_path;
        }
    }

    if (isAdmin() && $memory['user_id'] != $user_id) {
    $lastEditedBy = "Destiny";
    } else {
        $lastEditedBy = null;
    }


    // Update DB (admin can skip user_id check)
    if (isAdmin()) {
        $stmt = $conn->prepare("UPDATE memories 
            SET title = ?, description = ?, image_path = ?, tags = ?, privacy = ?, last_edited_by = ?
            WHERE id = ?");
        $stmt->bind_param("ssssssi", $title, $description, $image_path, $tags, $privacy, $lastEditedBy, $memory_id);
    } else {
        $stmt = $conn->prepare("UPDATE memories 
            SET title = ?, description = ?, image_path = ?, tags = ?, privacy = ?, last_edited_by = ?
            WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssssii", $title, $description, $image_path, $tags, $privacy, $lastEditedBy, $memory_id, $user_id);
    }


    if ($stmt->execute()) {
        header("Location: memories.php?msg=updated");
        exit;
    } else {
        $err = "Something went wrong while updating 💔.";
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
      <label class="block mb-1 font-medium">Image</label>
      <?php if ($memory['image_path']): ?>
        <img src="<?php echo $memory['image_path']; ?>" alt="Current Image" class="w-40 h-40 object-cover rounded mb-2">
      <?php endif; ?>
      <input type="file" name="image" accept="image/*" class="w-full">
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
