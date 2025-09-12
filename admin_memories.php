<?php
include 'config/db.php';
include 'includes/header.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: memories.php");
    exit;
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$statusFilter = $_GET['status'] ?? 'all';

$sql = "
  SELECT m.id, m.title, m.created_at, m.privacy, m.media_path, m.media_type, m.status,
        u.display_name,
        (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
  FROM memories m
  JOIN users u ON m.user_id = u.id
  WHERE 1
";

$params = [];
$types = '';

if ($search) {
    $sql .= " AND (m.title LIKE CONCAT('%', ?, '%') OR u.display_name LIKE CONCAT('%', ?, '%'))";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

if ($statusFilter !== 'all') {
    $sql .= " AND m.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="max-w-6xl mx-auto">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6 text-center">🌺 Manage Memories 🌺</h1>

  <!-- 🔎 Search + Filter -->
  <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
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

    <form method="GET" class="flex items-center space-x-2">
      <?php if ($search): ?>
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
      <?php endif; ?>
      <select name="status" onchange="this.form.submit()"
              class="border rounded-lg px-3 py-2">
        <option value="all" <?php if($statusFilter==='all') echo 'selected'; ?>>All</option>
        <option value="pending" <?php if($statusFilter==='pending') echo 'selected'; ?>>⏳ Pending</option>
        <option value="approved" <?php if($statusFilter==='approved') echo 'selected'; ?>>✅ Approved</option>
        <option value="rejected" <?php if($statusFilter==='rejected') echo 'selected'; ?>>❌ Rejected</option>
      </select>
    </form>
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
          <th class="px-4 py-2 text-left">Status</th>
          <th class="px-4 py-2 text-left">❤️ Likes</th>
          <th class="px-4 py-2 text-left">Date</th>
          <th class="px-4 py-2 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-t hover:bg-pink-50">
              <td class="px-4 py-2">
                <?php if ($row['media_path']): ?>
                  <?php if ($row['media_type'] === 'video'): ?>
                    <video src="<?php echo $row['media_path']; ?>" 
                          class="h-12 w-12 object-cover rounded-lg" 
                          autoplay muted loop playsinline></video>
                  <?php else: ?>
                    <img src="<?php echo $row['media_path']; ?>" 
                        class="h-12 w-12 object-cover rounded-lg">
                  <?php endif; ?>
                <?php else: ?>
                  <div class="h-12 w-12 bg-pink-50 flex items-center justify-center text-pink-300">🌸</div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($row['title']); ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($row['display_name']); ?></td>
              <td class="px-4 py-2"><?php echo $row['privacy']==='private' ? '🔒 Private' : '🌍 Public'; ?></td>
              <td class="px-4 py-2">
                <?php if ($row['status']==='pending'): ?>
                  <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">⏳ Pending</span>
                <?php elseif ($row['status']==='approved'): ?>
                  <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">✅ Approved</span>
                <?php else: ?>
                  <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">❌ Rejected</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2 text-center"><?php echo $row['like_count']; ?></td>
              <td class="px-4 py-2"><?php echo date("M j, Y", strtotime($row['created_at'])); ?></td>
              <td class="px-4 py-2 text-center space-x-2">
                <a href="memory.php?id=<?php echo $row['id']; ?>" 
                   class="text-blue-600 hover:underline">View</a>
                <a href="admin_delete_memory.php?id=<?php echo $row['id']; ?>" 
                   onclick="return confirm('Delete this memory?')" 
                   class="text-red-600 hover:underline">Delete</a>

                <?php if ($row['status']==='pending'): ?>
                  <a href="update_status.php?id=<?php echo $row['id']; ?>&action=approve"
                     class="text-green-600 hover:underline ml-2">Approve</a>
                  <a href="update_status.php?id=<?php echo $row['id']; ?>&action=reject"
                     class="text-yellow-600 hover:underline ml-2">Reject</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
              No memories found 🌹
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
