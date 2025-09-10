<?php
include '../config/db.php';
include '../includes/header.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: ../memories.php");
    exit;
}

// Handle search query (title or user)
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if ($search) {
    $stmt = $conn->prepare("
        SELECT m.id, m.title, m.created_at, m.privacy, m.image_path, u.display_name
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE m.title LIKE CONCAT('%', ?, '%') 
           OR u.display_name LIKE CONCAT('%', ?, '%')
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT m.id, m.title, m.created_at, m.privacy, m.image_path, u.display_name
        FROM memories m
        JOIN users u ON m.user_id = u.id
        ORDER BY m.created_at DESC
    ");
}
?>

<div class="max-w-6xl mx-auto">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6 text-center">🌺 Manage Memories 🌺</h1>

  <!-- ✅ Feedback Banners -->
  <?php if (isset($_GET['msg']) || isset($_GET['err'])): ?>
    <?php
      $message = '';
      $classes = '';

      if ($_GET['msg'] === 'deleted') {
          $message = "Memory deleted successfully 🗑️";
          $classes = "bg-red-100 text-red-700";
      } elseif ($_GET['err'] === 'invalid') {
          $message = "Invalid action 🚫";
          $classes = "bg-gray-100 text-gray-700";
      }
    ?>
    <?php if ($message): ?>
      <div id="flash-message" class="<?php echo $classes; ?> px-4 py-2 rounded-lg mb-6 text-center transition-opacity duration-1000">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const flash = document.getElementById("flash-message");
      if (flash) {
        setTimeout(() => {
          flash.classList.add("opacity-0");
          setTimeout(() => flash.remove(), 1000);
        }, 3000);
      }
    });
  </script>

  <!-- 🔎 Search Bar -->
  <div class="flex justify-between items-center mb-4">
    <form method="GET" class="flex space-x-2">
      <input type="text" name="search"
             value="<?php echo htmlspecialchars($search); ?>"
             placeholder="Search by title or user..."
             class="border rounded-lg px-3 py-2 w-64">
      <button type="submit" 
              class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition">
        Search
      </button>
    </form>

    <?php if ($search): ?>
      <a href="memories.php" class="text-sm text-gray-500 hover:text-gray-700">Clear Search ✖</a>
    <?php endif; ?>
  </div>

  <!-- 📋 Memories Table -->
  <div class="overflow-x-auto">
    <table class="w-full border border-gray-200 rounded-lg shadow-sm">
      <thead class="bg-pink-100 text-pink-700">
        <tr>
          <th class="px-4 py-2 text-left">Preview</th>
          <th class="px-4 py-2 text-left">Title</th>
          <th class="px-4 py-2 text-left">User</th>
          <th class="px-4 py-2 text-left">Privacy</th>
          <th class="px-4 py-2 text-left">Date</th>
          <th class="px-4 py-2 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-t hover:bg-pink-50">
              <td class="px-4 py-2">
                <?php if ($row['image_path']): ?>
                  <img src="../<?php echo $row['image_path']; ?>" class="h-12 w-12 object-cover rounded-lg">
                <?php else: ?>
                  <div class="h-12 w-12 bg-pink-50 flex items-center justify-center text-pink-300">🌸</div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($row['title']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($row['display_name']); ?></td>
              <td class="px-4 py-2">
                <?php echo $row['privacy'] === 'private' ? '🔒 Private' : '🌍 Public'; ?>
              </td>
              <td class="px-4 py-2"><?php echo date("M j, Y", strtotime($row['created_at'])); ?></td>
              <td class="px-4 py-2 text-center space-x-2">
                <a href="../memory.php?id=<?php echo $row['id']; ?>" 
                   class="text-blue-600 hover:underline">View</a>
                <a href="delete_memory.php?id=<?php echo $row['id']; ?>" 
                   onclick="return confirm('Delete this memory?')" 
                   class="text-red-600 hover:underline">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
              No memories found 🌹
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
