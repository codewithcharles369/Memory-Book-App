<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user role
$role = 'user';
$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
if ($roleResult && $roleResult->num_rows > 0) {
    $role = $roleResult->fetch_assoc()['role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $tags        = trim($_POST['tags']);
    $privacy     = $_POST['privacy'];

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $media_path = null;
    $media_type = null;

    if (!empty($_FILES['media']['name'])) {
        $fileTmp  = $_FILES['media']['tmp_name'];
        $fileName = basename($_FILES['media']['name']);
        $fileSize = $_FILES['media']['size'];
        $fileType = mime_content_type($fileTmp) ?: $_FILES['media']['type'];

        if (str_starts_with($fileType, 'image/')) {
            $media_type = 'image';
        } elseif (str_starts_with($fileType, 'video/')) {
            if ($fileSize > 10 * 1024 * 1024) {
                $err = "Video file must be less than 10MB.";
            } else {
                $media_type = 'video';
            }
        } else {
            $err = "Only images or videos are allowed.";
        }

        if (empty($err)) {
            $media_path = $uploadDir . time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
            if (!move_uploaded_file($fileTmp, $media_path)) {
                $err = "Upload failed. Please try again.";
            }
        }
    } else {
        $err = "Please upload an image or a video.";
    }

    $status = ($role === 'admin') ? 'approved' : 'pending';

    if (empty($err)) {
        $stmt = $conn->prepare("INSERT INTO memories 
            (user_id, title, description, media_path, media_type, tags, privacy, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id, $title, $description, $media_path, $media_type, $tags, $privacy, $status);

        if ($stmt->execute()) {
            header("Location: memories.php?msg=added");
            exit;
        } else {
            $err = "Something went wrong. Please try again 💔.";
        }
    }
}
?>


<div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl shadow">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6">Add a Memory 🌸</h1>

  <?php if (!empty($err)): ?>
    <p class="bg-red-100 text-red-600 p-2 rounded mb-4"><?php echo $err; ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-4">
    <div>
      <label class="block mb-1 font-medium">Title</label>
      <input type="text" name="title" class="w-full border rounded-lg p-2" required>
    </div>

    <div>
      <label class="block mb-1 font-medium">Description</label>
      <textarea name="description" rows="4" class="w-full border rounded-lg p-2" placeholder="Write something beautiful..." required></textarea>
    </div>

    <div>
      <label class="block mb-1 font-medium">Upload Image or Video</label>
      <input type="file" name="media" accept="image/*,video/*" class="w-full" required>
      <p class="text-xs text-gray-500 mt-1">Video must be less than 10MB</p>
    </div>

    <div>
      <label class="block mb-1 font-medium">Tags (comma separated)</label>
      <input type="text" name="tags" class="w-full border rounded-lg p-2" placeholder="love, friendship, fun">
    </div>

    <div>
      <label class="block mb-1 font-medium">Privacy</label>
      <select name="privacy" class="w-full border rounded-lg p-2">
        <option value="public">Public 🌍</option>
        <option value="private">Private 🔒</option>
      </select>
    </div>

    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded-xl hover:bg-pink-700 transition">
      Save Memory
    </button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
