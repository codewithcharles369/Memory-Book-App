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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_upload'])) {
    // Handle AJAX upload request
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
                echo json_encode(['status' => 'error', 'message' => 'Video file must be less than 10MB.']);
                exit;
            } else {
                $media_type = 'video';
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Only images or videos are allowed.']);
            exit;
        }

        $safeName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileName);
        $media_path = $uploadDir . time() . "_" . $safeName;

        if (!move_uploaded_file($fileTmp, $media_path)) {
            echo json_encode(['status' => 'error', 'message' => 'Upload failed. Please try again.']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Please upload an image or a video.']);
        exit;
    }

    $status = ($role === 'admin') ? 'approved' : 'pending';

    $stmt = $conn->prepare("INSERT INTO memories 
        (user_id, title, description, media_path, media_type, tags, privacy, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $title, $description, $media_path, $media_type, $tags, $privacy, $status);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Please try again 💔.']);
    }
    exit;
}
?>

<div class="max-w-2xl mx-auto bg-white p-6 rounded-2xl shadow">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6">Add a Memory 🌸</h1>

  <form id="memoryForm" enctype="multipart/form-data" class="space-y-4">
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
      <p class="text-xs text-pink-600 mt-1">
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

<!-- ✅ Upload Progress Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl p-6 text-center shadow-lg w-80">
    <div class="mb-3 text-pink-600 font-medium">Uploading your memory... 🌸</div>
    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
      <div id="uploadProgress" class="bg-pink-500 h-3 w-0 transition-all"></div>
    </div>
    <div id="progressText" class="text-sm mt-2 text-gray-600">0%</div>
  </div>
</div>

<!-- ✅ Success Toast -->
<div id="successToast" class="fixed top-5 right-5 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg hidden z-50">
  Memory added successfully 🎉
</div>

<script>
const form = document.getElementById('memoryForm');
const mediaInput = document.getElementById('mediaInput');
const modal = document.getElementById('uploadModal');
const bar = document.getElementById('uploadProgress');
const progressText = document.getElementById('progressText');
const toast = document.getElementById('successToast');

form.addEventListener('submit', e => {
  e.preventDefault();

  const file = mediaInput.files[0];
  if (!file) return alert("Please select an image or a video.");
  if (file.type.startsWith('video/') && file.size > 10 * 1024 * 1024) {
    return alert('⚠️ Video is too large! Please keep it under 10MB.');
  }

  const formData = new FormData(form);
  formData.append('ajax_upload', '1');

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
        toast.classList.remove('hidden');
        toast.classList.add('animate-fade-in');
        setTimeout(() => {
          toast.classList.add('hidden');
          window.location.href = 'memories.php?msg=added';
        }, 1500);
      } else {
        alert(res.message || 'Upload failed.');
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

<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
</style>

<?php include 'includes/footer.php'; ?>
