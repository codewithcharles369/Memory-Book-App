<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get memory ID
if (!isset($_GET['id'])) {
    header("Location: memories.php");
    exit;
}

$memory_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get user role
$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$userRole = $roleStmt->get_result()->fetch_assoc()['role'] ?? 'user';
$isAdmin = ($userRole === 'admin');

// Fetch memory (allow if public, owned by user, or admin)
if ($isAdmin) {
    $stmt = $conn->prepare("
        SELECT m.*, u.display_name 
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE m.id = ?
    ");
    $stmt->bind_param("i", $memory_id);
} else {
    $stmt = $conn->prepare("
        SELECT m.*, u.display_name 
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE m.id = ? AND (m.privacy = 'public' OR m.user_id = ?)
    ");
    $stmt->bind_param("ii", $memory_id, $user_id);
}

// ✅ Execute query and get result (this was missing for admin)
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "<p class='text-center text-gray-600'>This memory is private or does not exist 💔.</p>";
    include 'includes/footer.php';
    exit;
}

$memory = $result->fetch_assoc();

?>


<div class="max-w-3xl mx-auto bg-gradient-to-br from-pink-50 to-white rounded-2xl shadow-lg p-6 relative overflow-hidden">


  <!-- Decorative floating flowers -->
  <div class="absolute -top-3 -left-3 text-pink-200 text-5xl rotate-12">🌸</div>
  <div class="absolute bottom-2 right-2 text-pink-100 text-4xl animate-pulse">🌷</div>

  <!-- Image with double-tap like -->
  <div class="relative mb-6 group" id="memory-photo" data-id="<?php echo $memory['id']; ?>">
      <?php if ($memory['image_path']): ?>
          <img src="<?php echo $memory['image_path']; ?>" alt="Memory Image" 
              class="w-full h-full object-cover rounded-lg shadow-md cursor-pointer select-none">

          <!-- tape sticker effect -->
          <div class="absolute -top-3 left-1/3 bg-pink-200 w-24 h-6 rotate-6 rounded-sm opacity-70"></div>

          <!-- Top-right action buttons -->
          <div class="absolute top-3 right-3 flex space-x-2">
              <!-- Download button -->
              <a href="<?php echo $memory['image_path']; ?>" 
                download="memory_<?php echo $memory['id']; ?>.jpg"
                class="bg-white/80 hover:bg-white text-pink-600 px-3 py-2 rounded-full shadow-md transition flex items-center space-x-1"
                title="Download Image">
                <i class="fa-solid fa-download"></i> <span class="hidden sm:inline text-sm">Download</span>
              </a>

              <button id="share-btn"
                      class="bg-white/80 hover:bg-white text-pink-600 px-3 py-2 rounded-full shadow-md transition flex items-center space-x-1"
                      title="Share Memory"
                      data-url="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/memory.php?id=' . $memory['id']; ?>"
                      data-title="<?php echo htmlspecialchars($memory['title']); ?>">
                <i class="fa-solid fa-share-nodes"></i> <span class="hidden sm:inline text-sm">Share</span>
              </button>

          </div>
      <?php else: ?>
          <div class="w-full h-96 bg-pink-50 flex items-center justify-center text-pink-300 text-7xl rounded-lg">🌷</div>
      <?php endif; ?>

      <!-- Big fading heart -->
      <div id="big-heart" 
          class="absolute inset-0 flex items-center justify-center text-8xl opacity-0 pointer-events-none transition transform scale-50">
      </div>

      <!-- Burst hearts -->
      <div id="burst-container" class="absolute inset-0 pointer-events-none overflow-hidden"></div>
  </div>

<?php if ($memory['status'] === 'pending'): ?>
  <div class="mt-4">
    <span class="inline-block bg-yellow-500 text-white text-xs px-3 py-1 rounded-md shadow">
      ⏳ Pending
    </span>

    <?php if ($isAdmin): ?>
      <div class="mt-3 flex gap-3">
        <button 
          onclick="updateStatus(<?php echo $memory['id']; ?>, 'approve')"
          class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700">
          ✅ Approve
        </button>
        <button 
          onclick="updateStatus(<?php echo $memory['id']; ?>, 'reject')"
          class="bg-red-600 text-white px-4 py-2 rounded-lg shadow hover:bg-red-700">
          ❌ Reject
        </button><br>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($memory['status'] === 'rejected'): ?>
  <div class="mt-4">
    <span class="inline-block bg-red-600 text-white text-xs px-3 py-1 rounded-md shadow">
      🚫 Rejected
    </span>

    <?php if ($isAdmin): ?>
      <div class="mt-3 flex gap-3">
        <button 
          onclick="updateStatus(<?php echo $memory['id']; ?>, 'approve')"
          class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700">
          ✅ Approve
        </button>
        <a href="delete_memory.php?id=<?php echo $memory['id']; ?>" 
          onclick="return confirm('Are you sure you want to delete this memory? This cannot be undone. 💔');"
          class="bg-red-500 text-white px-4 py-2 rounded-lg shadow hover:bg-red-600 transition text-xl"
          title="Delete Memory">
          <i class="fa-solid fa-trash"></i> Delete
        </a>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>



  <!-- Title -->
<h1 class="text-4xl font-cursive text-pink-600 mb-2 border-b-2 border-pink-200 inline-block" data-aos="fade-up">
  <?php echo htmlspecialchars($memory['title']); ?>
<?php if ($isAdmin && $memory['privacy'] === 'private' && $memory['user_id'] != $user_id): ?>
    <span class="inline-flex items-center gap-1 text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded-full">
      🔒 Private
    </span>
  <?php endif; ?>
</h1>



  <!-- Description -->
  <p class="text-lg text-gray-700 mb-4 whitespace-pre-line leading-relaxed" data-aos="fade-up" data-aos-delay="100">
    <?php echo nl2br(htmlspecialchars($memory['description'])); ?>
  </p>




  <!-- Tags -->
  <?php if ($memory['tags']): ?>
    <div class="mb-4" data-aos="fade-up" data-aos-delay="200">
      <?php foreach (explode(',', $memory['tags']) as $tag): ?>
      <a href="memories.php?q=<?php echo urlencode(trim($tag)); ?>"
        class="inline-flex items-center gap-1 bg-pink-100 text-pink-700 text-sm px-3 py-1 rounded-full mr-2 hover:bg-pink-200 hover:shadow transition">
        <i class="fa-solid fa-hashtag"></i> <?php echo htmlspecialchars(trim($tag)); ?>
      </a>

      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Footer Info -->
<div class="text-sm text-gray-500 mt-4" data-aos="fade-up" data-aos-delay="300">
  <i class="fa-solid fa-user"></i> 
  <span class="font-semibold"><?php echo htmlspecialchars($memory['display_name']); ?></span><br>
  
  <?php if (!empty($memory['last_edited_by'])): ?>
    <div class="text-xs text-gray-400 mt-1">
      <i class="fa-solid fa-pen"></i> Edited by <?php echo htmlspecialchars($memory['last_edited_by']); ?>
    </div>
  <?php endif; ?>
  
  <i class="fa-regular fa-clock"></i> 
  <span><?php echo date("F j, Y, g:i a", strtotime($memory['created_at'])); ?></span>
  
  <?php if ($memory['privacy'] === "private"): ?>
    <span class="ml-2 text-pink-600">
      <i class="fa-solid fa-lock"></i> Private
    </span>
  <?php endif; ?>
</div>
  <!-- Sound effect for like -->
  <audio id="like-sound" src="assets/sounds/pop.mp3" preload="auto"></audio>  

<!-- Like Button + Counter -->
<div class="mt-6 flex items-center space-x-3">
  <button id="like-btn" 
          data-id="<?php echo $memory['id']; ?>" 
          class="flex items-center space-x-1 text-pink-500 hover:text-pink-600 focus:outline-none">
      <span id="like-icon" class="text-2xl">
        <?php
          $check = $conn->prepare("SELECT id FROM likes WHERE memory_id = ? AND user_id = ?");
          $check->bind_param("ii", $memory['id'], $user_id);
          $check->execute();
          $liked = $check->get_result()->num_rows > 0;
          echo $liked ? "❤️" : "🤍";
        ?>
      </span>
      <span id="like-count" class="text-lg font-semibold cursor-pointer hover:underline">
        <?php
          $count = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE memory_id = ?");
          $count->bind_param("i", $memory['id']);
          $count->execute();
          $total = $count->get_result()->fetch_assoc()['total'];
          echo $total;
        ?>
      </span>
  </button>
</div>

<!-- Likes List Modal -->
<div id="likes-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-2xl shadow-lg w-80 max-h-[70vh] overflow-y-auto p-4 relative">
    <h2 class="text-xl font-cursive text-pink-600 mb-3">❤️ Likes</h2>
    <button id="close-modal" class="absolute top-2 right-3 text-gray-400 hover:text-gray-600">✖</button>
    <ul id="likes-list" class="space-y-2">
      <li class="text-gray-500 text-sm italic">Loading...</li>
    </ul>
  </div>
</div>



<!-- Edit/Delete Buttons (Owner or Admin) -->
<?php if ($memory['user_id'] == $_SESSION['user_id'] || isAdmin()): ?>
  <div class="fixed bottom-6 right-6 flex flex-col gap-3 z-40">
    <a href="edit_memory.php?id=<?php echo $memory['id']; ?>" 
      class="bg-pink-500 text-white w-12 h-12 flex items-center justify-center rounded-full shadow-lg hover:bg-pink-600 transition text-xl"
      title="Edit Memory">
      <i class="fa-solid fa-pen-to-square"></i>
    </a>
    <a href="delete_memory.php?id=<?php echo $memory['id']; ?>" 
      onclick="return confirm('Are you sure you want to delete this memory? This cannot be undone. 💔');"
      class="bg-red-500 text-white w-12 h-12 flex items-center justify-center rounded-full shadow-lg hover:bg-red-600 transition text-xl"
      title="Delete Memory">
      <i class="fa-solid fa-trash"></i>
    </a>

  </div>
<?php endif; ?>


<div class="max-w-3xl mx-auto mt-6 text-center">
  <a href="memories.php" class="text-pink-600 hover:underline">← Back to Memories</a>
</div>

<?php
// --- Related Memories ---
$tags = array_filter(array_map('trim', explode(',', $memory['tags'] ?? '')));

if (!empty($tags)) {
    // Build a WHERE clause for tags
    $likeClauses = [];
    $params = [];
    $types = "";

    foreach ($tags as $tag) {
        $likeClauses[] = "m.tags LIKE ?";
        $params[] = "%" . $tag . "%";
        $types .= "s";
    }

    $sql = "
        SELECT m.id, m.title, m.image_path, u.display_name, 
               (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE m.privacy = 'public' 
          AND m.id != ? 
          AND (" . implode(" OR ", $likeClauses) . ")
        ORDER BY RAND()
        LIMIT 3
    ";

    $relatedStmt = $conn->prepare($sql);
    $types = "i" . $types;
    $params = array_merge([$memory_id], $params);
    $relatedStmt->bind_param($types, ...$params);
} else {
    // Fallback: just random public memories
    $relatedStmt = $conn->prepare("
        SELECT m.id, m.title, m.image_path, u.display_name,
               (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
        FROM memories m
        JOIN users u ON m.user_id = u.id
        WHERE m.privacy = 'public' AND m.id != ? AND  m.status = 'approved'
        ORDER BY RAND()
        LIMIT 3
    ");
    $relatedStmt->bind_param("i", $memory_id);
}

$relatedStmt->execute();
$related = $relatedStmt->get_result();
?>

<div class="max-w-5xl mx-auto mt-12">
  <h2 class="text-2xl font-cursive text-pink-600 mb-6 text-center"> <i class="fa-solid fa-spa"></i> Related Memories  <i class="fa-solid fa-spa"></i></h2>

  <?php if ($related->num_rows > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <?php while ($rel = $related->fetch_assoc()): ?>
        <a href="memory.php?id=<?php echo $rel['id']; ?>" 
           class="block bg-white shadow-lg rounded-xl overflow-hidden transform hover:scale-105 transition duration-300"
           data-aos="fade-up">
          
          <?php if ($rel['image_path']): ?>
            <img src="<?php echo $rel['image_path']; ?>" 
                 alt="Memory Image" 
                 class="w-full h-48 object-cover">
          <?php else: ?>
            <div class="w-full h-48 bg-pink-50 flex items-center justify-center text-pink-300 text-5xl">🌷</div>
          <?php endif; ?>

          <div class="p-4 text-center">
            <p class="font-cursive text-lg text-gray-700">
              <?php echo htmlspecialchars($rel['title']); ?>
            </p>
            <p class="text-xs text-gray-500 italic mb-1">
              by <?php echo htmlspecialchars($rel['display_name']); ?>
            </p>
            <p class="text-sm text-pink-600"><i class="fa-solid fa-heart text-pink-500"></i> <?php echo $rel['like_count']; ?></p>
          </div>
        </a>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-center text-gray-500">No related memories found 💭</p>
  <?php endif; ?>
</div>

<!-- Toast notification -->
<div id="toast" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-pink-600 text-white px-4 py-2 rounded-xl shadow-lg opacity-0 transition-opacity duration-500 z-50">
  📋 Link copied!
</div>


<script>
// Share button handler with cute toast fallback
document.addEventListener("DOMContentLoaded", () => {
  const shareBtn = document.getElementById("share-btn");
  const toast = document.getElementById("toast");

  if (shareBtn) {
    shareBtn.addEventListener("click", async () => {
      const url = shareBtn.dataset.url;
      const title = shareBtn.dataset.title;

      if (navigator.share) {
        try {
          await navigator.share({
            title: "🌸 Destiny’s Memory Garden",
            text: title,
            url: url,
          });
        } catch (err) {
          console.log("Share cancelled:", err);
        }
      } else {
        // fallback: copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
          // Show toast
          toast.classList.remove("opacity-0");
          toast.classList.add("opacity-100");

          setTimeout(() => {
            toast.classList.remove("opacity-100");
            toast.classList.add("opacity-0");
          }, 2000); // hide after 2s
        });
      }
    });
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const photo = document.getElementById("memory-photo");
  const bigHeart = document.getElementById("big-heart");
  const likeBtn = document.getElementById("like-btn");
  const likeIcon = document.getElementById("like-icon");
  const likeCount = document.getElementById("like-count");
  let lastTap = 0;

const memoryId = <?php echo json_encode($memory['id']); ?>;

document.addEventListener("DOMContentLoaded", () => {
  const likeCount = document.getElementById("like-count");
  const likesModal = document.getElementById("likes-modal");
  const closeModal = document.getElementById("close-modal");
  const likesList = document.getElementById("likes-list");

  likeCount.addEventListener("click", () => {
    likesModal.classList.remove("hidden");
    likesList.innerHTML = `<li class="text-gray-500 text-sm italic">Loading...</li>`;

    fetch("api/get_likes.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "memory_id=" + encodeURIComponent(memoryId)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        likesList.innerHTML = data.friends.length
          ? data.friends.map(f => `<li class="p-2 bg-pink-50 rounded-lg">💖 ${f.name}</li>`).join('')
          : `<li class="text-gray-500 text-sm italic">No likes yet</li>`;
      } else {
        likesList.innerHTML = `<li class="text-red-500 text-sm">Error: ${data.error}</li>`;
      }
    })
    .catch(err => {
      console.error(err);
      likesList.innerHTML = `<li class="text-red-500 text-sm">Network error</li>`;
    });
  });

  closeModal.addEventListener("click", () => {
    likesModal.classList.add("hidden");
  });
});





  // Handle double-tap on photo
  photo.addEventListener("click", () => {
    const now = Date.now();
    if (now - lastTap < 300) {
      triggerLike(photo.dataset.id, true);
    }
    lastTap = now;
  });

  // Handle single tap on heart button
  likeBtn.addEventListener("click", () => {
    triggerLike(likeBtn.dataset.id, false);
  });

function triggerLike(memoryId, showBigHeart) {
  fetch("api/toggle_like.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "memory_id=" + memoryId
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      likeIcon.textContent = data.liked ? "❤️" : "🤍";
      likeCount.textContent = data.total;

      if (showBigHeart) {
        // Big fading heart
        bigHeart.textContent = data.liked ? "❤️" : "🤍";
        bigHeart.classList.remove("opacity-0");
        bigHeart.classList.add("opacity-100", "scale-100");

        setTimeout(() => {
          bigHeart.classList.remove("opacity-100", "scale-100");
          bigHeart.classList.add("opacity-0", "scale-50");
        }, 800);

        // Burst effect only when liking
        if (data.liked) {
          createBurst();

                    // Play pop sound
          const sound = document.getElementById("like-sound");
          sound.currentTime = 0; // rewind if already playing
          sound.play().catch(err => console.log("Sound blocked:", err));
        }
      }
    }
  })
  .catch(err => console.error(err));
}

function createBurst() {
  const burstContainer = document.getElementById("burst-container");

  for (let i = 0; i < 6; i++) {
    const heart = document.createElement("div");
    heart.classList.add("burst-heart");
    heart.textContent = "❤️";

    // Random direction
    const angle = (Math.random() * 360) * (Math.PI / 180);
    const distance = 80 + Math.random() * 40;
    const x = Math.cos(angle) * distance + "px";
    const y = Math.sin(angle) * distance + "px";

    heart.style.setProperty("--x", x);
    heart.style.setProperty("--y", y);

    heart.style.left = "50%";
    heart.style.top = "50%";
    heart.style.transform = "translate(-50%, -50%)";

    burstContainer.appendChild(heart);

    // Remove after animation ends
    setTimeout(() => heart.remove(), 1000);
  }
}


});


</script>

<audio id="like-sound" src="assets/sounds/pop.mp3" preload="auto"></audio>

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


<?php include 'includes/footer.php'; ?>
