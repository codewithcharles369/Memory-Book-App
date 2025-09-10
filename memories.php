<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Search filter
$search = $_GET['q'] ?? '';
$searchLike = '%' . $search . '%';

if ($search) {
    $stmt = $conn->prepare("
        SELECT m.*, u.display_name 
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE (m.privacy = 'public' OR m.user_id = ?)
          AND (m.title LIKE ? OR m.description LIKE ? OR m.tags LIKE ?)
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("isss", $user_id, $searchLike, $searchLike, $searchLike);
} else {
    $stmt = $conn->prepare("
        SELECT m.*, u.display_name 
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE m.privacy = 'public' OR m.user_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
}


// Fetch memories (show public + user’s private)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT m.*, u.display_name 
    FROM memories m
    JOIN users u ON m.user_id = u.id
    WHERE m.privacy = 'public' OR m.user_id = ?
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="max-w-7xl mx-auto px-4">
  <h1 class="text-4xl font-cursive text-pink-600 mb-6 text-center" data-aos="fade-down">
    📸 Cleopatra’s Memory Gallery 🌸
  </h1>

  <!-- 🔍 Search + Filter -->
  <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
    <form method="GET" action="memories.php" class="flex items-center gap-2 w-full md:w-1/2">
      <input type="text" name="q" placeholder="Search memories or tags..." 
             value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
             class="flex-1 border rounded-lg p-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-300">
      <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700">
        Search
      </button>
    </form>
  </div>

  <?php if ($result->num_rows > 0): ?>
    <div class="columns-1 sm:columns-2 md:columns-3 lg:columns-4 gap-6 [column-fill:_balance]">
      <?php $delay = 0; ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <a href="memory.php?id=<?php echo $row['id']; ?>" 
           class="mb-6 break-inside-avoid block bg-white shadow-md rounded-lg overflow-hidden hover:shadow-2xl hover:-translate-y-1 transition duration-300"
           data-aos="zoom-in"
           data-aos-delay="<?php echo $delay; ?>">

          <!-- Photo -->
          <?php if ($row['image_path']): ?>
            <img src="<?php echo $row['image_path']; ?>" alt="Memory Image" 
                 class="w-full rounded-t-lg">
          <?php else: ?>
            <div class="w-full h-48 bg-pink-50 flex items-center justify-center text-pink-300 text-6xl">🌷</div>
          <?php endif; ?>

          <!-- Caption -->
          <div class="p-3 text-center">
            <p class="font-cursive text-lg text-gray-700">
              <?php echo htmlspecialchars($row['title']); ?>
            </p>
            <p class="text-xs text-gray-500 italic">
              by <?php echo htmlspecialchars($row['display_name']); ?>
            </p>
          </div>
        </a>
        <?php $delay += 150; ?>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-center text-gray-600">No memories yet. Be the first to share something 🌹</p>
  <?php endif; ?>
</div>


<!-- Floating Add Memory Button -->
<a href="add-memory.php" 
   class="fixed bottom-6 right-6 bg-pink-600 text-white w-14 h-14 flex items-center justify-center rounded-full shadow-lg hover:bg-pink-700 hover:scale-110 transition transform text-2xl font-bold">
   +
</a>

<?php include 'includes/footer.php'; ?>
