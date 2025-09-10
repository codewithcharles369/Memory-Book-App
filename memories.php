<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Search filter
$search = $_GET['q'] ?? '';
$searchLike = '%' . $search . '%';

// Sorting filter
$sort = $_GET['sort'] ?? 'latest';
$orderBy = "m.created_at DESC"; // default
$extra = "";
$joinLikes = "";

// Sorting modes
if ($sort === "liked") {
    $orderBy = "like_count DESC, m.created_at DESC";
} elseif ($sort === "mine") {
    $extra = "AND m.user_id = ?";
} elseif ($sort === "favorites") {
    $joinLikes = "JOIN likes l ON m.id = l.memory_id";
    $extra = "AND l.user_id = ?";
}

// Base SQL
$sql = "
    SELECT m.*, u.display_name,
           (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
    FROM memories m
    JOIN users u ON m.user_id = u.id
    $joinLikes
    WHERE (m.privacy = 'public' OR m.user_id = ?)
    $extra
";

// Add search filter
if (!empty($search)) {
    $sql .= " AND (m.title LIKE ? OR m.description LIKE ? OR m.tags LIKE ?)";
}

// Final order
$sql .= " ORDER BY $orderBy";

$stmt = $conn->prepare($sql);

// Build dynamic parameters
$params = [];
$types = "";

// First user_id (for public/private check)
$params[] = $user_id;
$types .= "i";

// Extra user_id (mine / favorites)
if ($sort === "mine" || $sort === "favorites") {
    $params[] = $user_id;
    $types .= "i";
}

// Search params
if (!empty($search)) {
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

// Bind if we actually have params
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>



<div class="max-w-7xl mx-auto px-4">
  <h1 class="text-4xl font-cursive text-pink-600 mb-6 text-center" data-aos="fade-down">
    📸 Cleopatra’s Memory Gallery 🌸
  </h1>

  <!-- 🔍 Search + Filter -->
<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
  <form method="GET" action="memories.php" class="flex items-center gap-2 w-full md:w-2/3">
    <input type="text" name="q" placeholder="Search memories or tags..." 
           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
           class="flex-1 border rounded-lg p-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-300">
    <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700">
      <i class="fa-solid fa-magnifying-glass"></i>
    </button>
  </form>

  <!-- Sort Filter -->
  <form method="GET" action="memories.php">
    <select name="sort" onchange="this.form.submit()" 
            class="border rounded-lg p-2 shadow-sm focus:ring-2 focus:ring-pink-300">
      <option value="latest" <?php if(($_GET['sort'] ?? '')=='latest') echo 'selected'; ?>>📅 Latest</option>
      <option value="liked" <?php if(($_GET['sort'] ?? '')=='liked') echo 'selected'; ?>>❤️ Most Liked</option>
      <option value="mine" <?php if(($_GET['sort'] ?? '')=='mine') echo 'selected'; ?>>🙋 My Memories</option>
    </select>
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
          <p class="text-xs text-gray-500 italic mb-2">
            <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($row['display_name']); ?>
          </p>
          <div class="flex justify-center items-center text-pink-600 gap-2 text-sm">
            <i class="fa-solid fa-heart"></i> 
            <?php echo $row['like_count'] ?? 0; ?>
          </div>
        </div>

        </a>
        <?php $delay += 150; ?>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-600 mt-10">
  <i class="fa-regular fa-images text-6xl text-pink-300 mb-3"></i>
  <p>No memories yet. Be the first to share something beautiful!</p>
  <a href="add-memory.php" 
     class="inline-block mt-4 bg-pink-600 text-white px-6 py-2 rounded-lg shadow hover:bg-pink-700 transition">
     <i class="fa-solid fa-plus"></i> Add Memory
  </a>
</div>

  <?php endif; ?>
</div>


<!-- Floating Add Memory Button -->
<a href="add-memory.php" 
   class="fixed bottom-6 right-6 bg-pink-600 text-white w-14 h-14 flex items-center justify-center rounded-full shadow-lg hover:bg-pink-700 hover:scale-110 transition transform text-2xl">
   <i class="fa-solid fa-plus"></i>
</a>


<?php include 'includes/footer.php'; ?>
