<?php
include 'includes/header.php';
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$memory_id = intval($_GET['id'] ?? 0);
$user_id   = $_SESSION['user_id'];

// Fetch memory based on role
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

// ✅ Handle AJAX update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_edit'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $tags        = trim($_POST['tags']);
    $privacy     = $_POST['privacy'];

    $media_path  = $memory['media_path'];
    $media_type  = $memory['media_type'];

    if (!empty($_FILES['media']['name']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['media']['size'] > 10 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'File too large. Max size is 10MB.']);
            exit;
        }

        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        $isVideo = in_array($ext, ['mp4','mov','webm']);
        $newPath = $uploadDir . time() . "_" . basename($_FILES['media']['name']);

        if (!move_uploaded_file($_FILES['media']['tmp_name'], $newPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Upload failed. Please try again.']);
            exit;
        }

        if ($media_path && file_exists($media_path)) unlink($media_path);

        $media_path = $newPath;
        $media_type = $isVideo ? 'video' : 'image';
    }

    $lastEditedBy = (isAdmin() && $memory['user_id'] != $user_id) ? "Destiny" : null;

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
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong while updating 💔.']);
    }
    exit;
}
?>

<div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl shadow">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6">Edit Memory ✏️</h1>

  <form id="editForm" enctype="multipart/form-data" class="space-y-4">
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
      <div id="currentMedia">
        <?php if ($memory['media_type'] === 'video'): ?>
          <video src="<?php echo htmlspecialchars($memory['media_path']); ?>" controls class="w-40 h-40 object-cover rounded mb-2"></video>
        <?php elseif ($memory['media_type'] === 'image'): ?>
          <img src="<?php echo htmlspecialchars($memory['media_path']); ?>" class="w-40 h-40 object-cover rounded mb-2">
        <?php endif; ?>
      </div>

      <label class="block mb-1 font-medium mt-2">Replace Media (optional)</label>
      <input type="file" name="media" accept="image/*,video/*" class="w-full" id="mediaInput">
      <p class="text-xs text-pink-600 mt-1">
        ⚡ Only very short videos are allowed — keep them under <strong>10MB</strong> 🌸
      </p>

      <!-- ✅ Live preview -->
      <div id="previewContainer" class="mt-3 hidden">
        <p class="text-sm text-gray-600 mb-1">New media preview:</p>
        <div id="previewBox" class="w-40 h-40 rounded overflow-hidden shadow-md border"></div>
      </div>
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

<!-- ✅ Upload Progress Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl p-6 text-center shadow-lg w-80">
    <div class="mb-3 text-pink-600 font-medium">Updating your memory... 🌸</div>
    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
      <div id="uploadProgress" class="bg-pink-500 h-3 w-0 transition-all"></div>
    </div>
    <div id="progressText" class="text-sm mt-2 text-gray-600">0%</div>
  </div>
</div>

<script>
const form = document.getElementById('editForm');
const mediaInput = document.getElementById('mediaInput');
const previewContainer = document.getElementById('previewContainer');
const previewBox = document.getElementById('previewBox');
const currentMedia = document.getElementById('currentMedia');
const modal = document.getElementById('uploadModal');
const bar = document.getElementById('uploadProgress');
const progressText = document.getElementById('progressText');

// ✅ Preview and hide current media when selecting new one
mediaInput.addEventListener('change', () => {
  previewBox.innerHTML = '';
  const file = mediaInput.files[0];
  if (!file) {
    previewContainer.classList.add('hidden');
    currentMedia.classList.remove('hidden');
    return;
  }
  currentMedia.classList.add('hidden');
  const url = URL.createObjectURL(file);
  previewContainer.classList.remove('hidden');
  if (file.type.startsWith('video/')) {
    const vid = document.createElement('video');
    vid.src = url;
    vid.controls = true;
    vid.className = 'w-40 h-40 object-cover';
    previewBox.appendChild(vid);
  } else if (file.type.startsWith('image/')) {
    const img = document.createElement('img');
    img.src = url;
    img.className = 'w-40 h-40 object-cover';
    previewBox.appendChild(img);
  }
});

// ✅ AJAX upload with progress
form.addEventListener('submit', e => {
  e.preventDefault();
  const file = mediaInput.files[0];
  if (file && file.type.startsWith('video/') && file.size > 10 * 1024 * 1024) {
    return alert('⚠️ Video is too large! Please keep it under 10MB.');
  }

  const formData = new FormData(form);
  formData.append('ajax_edit', '1');

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '', true);

  xhr.upload.addEventListener('progress', e => {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      bar.style.width = percent + '%';
      progressText.textContent = percent + '%';
    }
  });

  xhr.onload = () => {
    modal.classList.add('hidden');
    if (xhr.status === 200) {
      const res = JSON.parse(xhr.responseText);
      if (res.status === 'success') {
        window.location.href = 'memories.php?msg=updated';
      } else {
        alert(res.message || 'Update failed.');
      }
    } else {
      alert('Server error. Try again.');
    }
  };

  modal.classList.remove('hidden');
  modal.classList.add('flex');
  xhr.send(formData);
});
</script>

<?php include 'includes/footer.php'; ?>
