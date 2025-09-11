<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current user role
$role = 'user';
$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();
if ($roleResult && $roleResult->num_rows > 0) {
    $roleRow = $roleResult->fetch_assoc();
    $role = $roleRow['role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $tags        = trim($_POST['tags']);
    $privacy     = $_POST['privacy'];

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $image_path = $uploadDir . time() . "_img_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    // ✅ Admins create APPROVED memories instantly
    $status = ($role === 'admin') ? 'approved' : 'pending';

    $stmt = $conn->prepare("INSERT INTO memories 
        (user_id, title, description, image_path, tags, privacy, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $title, $description, $image_path, $tags, $privacy, $status);

    if ($stmt->execute()) {
        header("Location: memories.php?msg=added");
        exit;
    } else {
        $err = "Something went wrong. Please try again 💔.";
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
      <label class="block mb-1 font-medium">Image</label>
      <input type="file" name="image" accept="image/*" class="w-full" required>
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
