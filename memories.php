<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if current user is admin
$is_admin = false;
$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleRes = $roleStmt->get_result();
if ($roleRes && $row = $roleRes->fetch_assoc()) {
    $is_admin = ($row['role'] === 'admin');
}



// Search filter
$search = $_GET['q'] ?? '';
$searchLike = '%' . $search . '%';

// Sorting filter
$sort = $_GET['sort'] ?? 'latest';
$orderBy = "m.created_at DESC"; // default
$extra = "";
$joinLikes = "";

if ($sort === "liked") {
    $orderBy = "like_count DESC, m.created_at DESC";
} elseif ($sort === "mine") {
    $extra = "AND m.user_id = ?";
} elseif ($sort === "favorites") {
    $joinLikes = "JOIN likes l ON m.id = l.memory_id";
    $extra = "AND l.user_id = ?";
} elseif ($sort === "private" && $is_admin) {
    $extra = "AND m.privacy = 'private'";
} elseif ($sort === "pending" && $is_admin) {
    $extra = "AND m.status = 'pending'";
} elseif ($sort === "rejected" && $is_admin) {
    $extra = "AND m.status = 'rejected'";
}




if ($is_admin) {
    // Admin can see all statuses including rejected
    $statusFilter = "1";
} else {
    // Users: show only approved public + their own approved/pending
    $statusFilter = "(
        (m.status = 'approved' AND m.privacy = 'public')
        OR (m.user_id = ? AND m.status IN ('approved','pending'))
    )";
}


$sql = "
    SELECT m.*, u.display_name,
           (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
    FROM memories m
    JOIN users u ON m.user_id = u.id
    $joinLikes
    WHERE ($statusFilter)
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

// Only non-admins need this privacy check param
if (!$is_admin) {
    $params[] = $user_id;
    $types .= "i";
}

// Extra filter param for mine/favorites
if ($sort === "mine" || $sort === "favorites") {
    $params[] = $user_id;
    $types .= "i";
}

// Search filters
if (!empty($search)) {
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}


if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}


// Bind if we actually have params
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div id="sparkles"></div>

<div class="flower" style="top:15%; left:10%;">🌸</div>
<div class="flower" style="top:50%; left:80%;">🌷</div>
<div class="flower" style="top:70%; left:40%;">💮</div>


<div class="max-w-7xl mx-auto px-4">
  <h1 class="text-4xl font-cursive text-pink-600 mb-6 text-center" data-aos="fade-down">
    📸 Destiny’s Memory Gallery 🌸
  </h1>

<div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
  <!-- 🔍 Search -->
  <form method="GET" action="memories.php" class="flex items-center gap-2 w-full md:w-1/2">
    <input type="text" name="q" placeholder="Search memories or tags..." 
           value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
           class="flex-1 border rounded-lg p-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-300">
    <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700">
      Search
    </button>
  </form>

  <!-- 🏷 Sort/Filter -->
  <form method="GET" action="memories.php" class="flex items-center gap-2">
    <?php if (!empty($_GET['q'])): ?>
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($_GET['q']); ?>">
    <?php endif; ?>
      <select name="sort" onchange="this.form.submit()"
              class="border rounded-lg p-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-pink-300">
        <option value="latest"   <?php if(($_GET['sort'] ?? '')==='latest') echo 'selected'; ?>>📅 Latest</option>
        <option value="liked"    <?php if(($_GET['sort'] ?? '')==='liked') echo 'selected'; ?>>❤️ Most Liked</option>
        <option value="mine"     <?php if(($_GET['sort'] ?? '')==='mine') echo 'selected'; ?>>👤 My Memories</option>
        <option value="favorites"<?php if(($_GET['sort'] ?? '')==='favorites') echo 'selected'; ?>>💖 Favorites</option>

    <?php if ($is_admin): ?>
      <option value="private"  <?php if(($_GET['sort'] ?? '')==='private')  echo 'selected'; ?>>🔒 Private Only</option>
      <option value="pending"  <?php if(($_GET['sort'] ?? '')==='pending')  echo 'selected'; ?>>⏳ Pending</option>
      <option value="rejected" <?php if(($_GET['sort'] ?? '')==='rejected') echo 'selected'; ?>>🚫 Rejected</option>
    <?php endif; ?>


      </select>
  </form>
</div>



<div class="max-w-7xl mx-auto px-4">

  <!-- 🌸 Trending Tags Bar -->
  <div class="flex gap-2 overflow-x-auto pb-3 mb-6 no-scrollbar">
    <?php
      $tagsRes = $conn->query("
        SELECT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ',', n.n), ',', -1)) AS tag
        FROM memories
        JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) n
        WHERE tags <> ''
      ");
      $tags = [];
      while ($t = $tagsRes->fetch_assoc()) {
          $tag = strtolower(trim($t['tag']));
          if ($tag !== '') $tags[$tag] = ($tags[$tag] ?? 0) + 1;
      }
      arsort($tags);
      foreach (array_slice(array_keys($tags), 0, 6) as $tag):
    ?>
      <a href="memories.php?q=<?php echo urlencode($tag); ?>"
         class="bg-pink-100 hover:bg-pink-200 text-pink-700 px-3 py-1 rounded-full text-sm whitespace-nowrap transition">
         #<?php echo htmlspecialchars($tag); ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- 🖼 Gallery Grid -->
  <?php if ($result->num_rows > 0): ?>
    <div id="memory-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <?php while ($row = $result->fetch_assoc()): ?>
        <a href="memory.php?id=<?php echo $row['id']; ?>" 
   class="memory-card-tilt group relative bg-white rounded-xl overflow-hidden shadow hover:shadow-xl transition transform <?php 
      echo ($row['status'] === 'pending') ? 'opacity-60' : ''; ?> <?php echo ($row['status'] === 'rejected') ? 'opacity-40 grayscale' : ''; ?>
" >


          <?php if ($row['image_path']): ?>
            <img src="<?php echo $row['image_path']; ?>" 
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
          <?php else: ?>
            <div class="w-full h-60 bg-pink-50 flex items-center justify-center text-pink-300 text-6xl">
              <i class="fa-solid fa-flower"></i>
            </div>
          <?php endif; ?>

          <!-- Overlay Info -->
          <div class="">
            <div class="absolute bottom-2 left-2 text-white">
              <p class="font-cursive text-xl "><?php echo htmlspecialchars($row['title']); ?></p>
              <div class="flex gap-2 text-xs text-pink-100 mt-1">
                <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($row['display_name']); ?></span>
                <span><i class="fa-solid fa-heart"></i> <?php echo $row['like_count'] ?? 0; ?></span>
              </div>
            </div>
          </div>

          <!-- 🔒 Private badge -->
          <?php if ($row['privacy'] === 'private' && ($row['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] === 'admin')): ?>
            <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-md flex items-center gap-1">
              <i class="fa-solid fa-lock"></i> Private
            </div>
          <?php endif; ?>

          <?php if ($row['status'] === 'pending'): ?>
            <div class="absolute top-2 left-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded-md shadow">
              ⏳ Pending
            </div>

            <?php if ($is_admin): ?>
              <div class="absolute bottom-2 right-2 flex gap-2">
                <button 
                  onclick="updateStatus(<?php echo $row['id']; ?>, 'approve')"
                  class="bg-green-600 text-white text-xs px-2 py-1 rounded-md hover:bg-green-700 shadow">
                  ✅ Approve
                </button>
                <button 
                  onclick="updateStatus(<?php echo $row['id']; ?>, 'reject')"
                  class="bg-red-600 text-white text-xs px-2 py-1 rounded-md hover:bg-red-700 shadow">
                  ❌ Reject
                </button>
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($row['status'] === 'rejected'): ?>
            <div class="absolute top-2 left-2 bg-red-600 text-white text-xs px-2 py-1 rounded-md shadow">
              🚫 Rejected
            </div>
          <?php endif; ?>



        </a>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="text-center text-gray-600 mt-10">
      <i class="fa-regular fa-images text-6xl text-pink-300 mb-3"></i>
      <p>No memories yet. Be the first to share something beautiful!</p>
    </div>
  <?php endif; ?>
</div>



<!-- Floating Add Memory Button -->
<a href="add-memory.php" 
   class="add-memory-btn fixed bottom-6 right-6 bg-pink-600 text-white w-14 h-14 flex items-center justify-center rounded-full shadow-lg hover:bg-pink-700 hover:scale-110 transition transform text-2xl">
<i class="fa-solid fa-plus"></i>
</a>


<?php include 'includes/footer.php'; ?>
<script>
// ✨ Sparkle generator
const sparkleContainer = document.getElementById('sparkles');
setInterval(() => {
  const s = document.createElement('div');
  s.className = 'sparkle';
  s.style.top = Math.random() * window.innerHeight + 'px';
  s.style.left = Math.random() * window.innerWidth + 'px';
  sparkleContainer.appendChild(s);
  setTimeout(() => s.remove(), 2000);
}, 300);

// 🌸 Parallax flowers
window.addEventListener('scroll', () => {
  const scrollY = window.scrollY;
  document.querySelectorAll('.flower').forEach((f, i) => {
    f.style.transform = `translateY(${scrollY * (0.1 + i*0.05)}px)`;
  });
});
</script>

<script>
function updateStatus(memoryId, action) {
  if (!confirm(`Are you sure you want to ${action} this memory?`)) return;

  fetch('update_status.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `memory_id=${memoryId}&action=${action}`
  })
  .then(res => res.text())
  .then(data => {
    if (data.trim() === 'success') {
      alert(`Memory ${action}d successfully`);
      location.reload();
    } else {
      alert('Error: ' + data);
    }
  })
  .catch(err => alert('Request failed'));
}
</script>
