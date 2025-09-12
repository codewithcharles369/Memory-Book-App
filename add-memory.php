<?php
include 'includes/header.php';
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Get user role
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

    if (!empty($_FILES['media']['name']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['media']['tmp_name'];
        $fileName = basename($_FILES['media']['name']);
        $fileSize = $_FILES['media']['size'];

        $fileType = (function_exists('mime_content_type') && is_file($fileTmp))
            ? mime_content_type($fileTmp)
            : ($_FILES['media']['type'] ?? 'unknown');

        if (strpos($fileType, 'image/') === 0) {
            $media_type = 'image';
        } elseif (strpos($fileType, 'video/') === 0) {
            if ($fileSize > 10 * 1024 * 1024) {
                $err = "Video file must be less than 10MB.";
            } else {
                $media_type = 'video';
            }
        } else {
            $err = "Only images or videos are allowed.";
        }

        if (empty($err)) {
            $safeName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
            $media_path = $uploadDir . time() . "_" . $safeName;

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
    <p class="bg-red-100 text-red-600 p-2 rounded mb-4"><?php echo htmlspecialchars($err); ?></p>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-4" id="memoryForm">
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
      <input type="file" name="media" accept="image/*,video/*" class="w-full" id="mediaInput" required>
      <p class="text-xs text-gray-500 mt-1 text-pink-600">
        ⚡ Only very short videos are allowed — keep them under <strong>10MB</strong> so your friends can enjoy them quickly 🌸
      </p>
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

<!-- ✅ Loading modal -->
<div id="loadingModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl p-6 text-center shadow-lg">
    <div class="animate-spin h-10 w-10 border-4 border-pink-500 border-t-transparent rounded-full mx-auto mb-4"></div>
    <p class="text-pink-600 font-medium">Uploading your memory... 🌸</p>
  </div>
</div>

<script>
const form = document.getElementById('memoryForm');
const mediaInput = document.getElementById('mediaInput');
const loadingModal = document.getElementById('loadingModal');

form.addEventListener('submit', e => {
  const file = mediaInput.files[0];
  if (file && file.type.startsWith('video/') && file.size > 10 * 1024 * 1024) {
    e.preventDefault();
    alert('⚠️ Video is too large! Please keep it under 10MB.');
    return;
  }
  // Show loading modal
  loadingModal.classList.remove('hidden');
  loadingModal.classList.add('flex');
});
</script>

<?php include 'includes/footer.php'; ?>
