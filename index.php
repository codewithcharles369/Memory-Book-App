<?php include 'includes/header.php'; ?>
<?php include 'config/db.php'; ?>


<?php
// Stats
$stats = $conn->query("
  SELECT 
    (SELECT COUNT(*) FROM memories WHERE status = 'approved') AS total_memories,
    (SELECT COUNT(*) FROM likes) AS total_likes,
    (SELECT COUNT(*) FROM users) AS total_users
")->fetch_assoc();


$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$pendingCount = 0;
if ($isAdmin) {
  $pendingResult = $conn->query("SELECT COUNT(*) AS c FROM memories WHERE status='pending'");
  $pendingCount = $pendingResult->fetch_assoc()['c'];
}



// Carousel data
$carousel = $conn->query("
  SELECT m.id, m.title, m.image_path, u.display_name,
         (SELECT COUNT(*) FROM likes WHERE memory_id = m.id) AS like_count
  FROM memories m
  JOIN users u ON m.user_id = u.id
  WHERE m.privacy='public' AND m.status = 'approved'
  ORDER BY m.created_at DESC
  LIMIT 10
");
?>

<!-- 🌸 Parallax Flowers Background -->
<div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
  <div class="flower absolute top-20 left-10 text-pink-100 text-5xl">🌸</div>
  <div class="flower absolute top-1/3 right-20 text-pink-200 text-6xl">🌷</div>
  <div class="flower absolute bottom-20 left-1/2 text-pink-100 text-4xl">💮</div>
</div>

<!-- 🌟 Hero Section -->
<div class="relative text-center py-28 hero-bg bg-gradient-to-b from-pink-50 to-white">

<div id="sparkles"></div>


  <!-- 💖 Destiny's Photo -->
  <img src="assets/images/destiny.jpg" 
     alt="Destiny"
     class="hero-photo w-40 h-40 object-cover rounded-full mx-auto mb-6 border-4 border-pink-200 shadow-lg animate-fadeIn">


  <h1 class="font-cursive text-6xl text-pink-600 mb-4 animate-fadeIn">Destiny’s Memory Garden</h1>
  <p class="max-w-xl mx-auto text-lg text-gray-700 mb-8 animate-slideUp">
    A magical scrapbook where friends can share their sweetest memories 🌹
  </p>
  <div class="flex justify-center gap-4 animate-slideUp">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="add-memory.php" class="px-6 py-3 bg-pink-600 text-white rounded-2xl shadow hover:bg-pink-700 transition">➕ Add Memory</a>
      <a href="memories.php" class="px-6 py-3 bg-white text-pink-600 rounded-2xl shadow hover:bg-pink-50 transition">📖 View Memories</a>
    <?php else: ?>
      <a href="login.php" class="px-6 py-3 bg-pink-600 text-white rounded-2xl shadow hover:bg-pink-700 transition">Login</a>
      <a href="register.php" class="px-6 py-3 bg-white text-pink-600 rounded-2xl shadow hover:bg-pink-50 transition">Register</a>
    <?php endif; ?>
  </div>
</div>


<!-- 🏆 Stats -->
<div class="grid grid-cols-3 max-w-4xl mx-auto text-center my-16 gap-6">
  <div class="bg-white rounded-2xl p-6 shadow">
    <div class="text-3xl text-pink-500">📸</div>
    <div class="text-2xl font-bold"><?php echo $stats['total_memories']; ?></div>
    <div class="text-gray-500 text-sm">Memories Shared</div>
  </div>
  <div class="bg-white rounded-2xl p-6 shadow">
    <div class="text-3xl text-pink-500">❤️</div>
    <div class="text-2xl font-bold"><?php echo $stats['total_likes']; ?></div>
    <div class="text-gray-500 text-sm">Total Likes</div>
  </div>
  <div class="bg-white rounded-2xl p-6 shadow">
    <div class="text-3xl text-pink-500">👥</div>
    <div class="text-2xl font-bold"><?php echo $stats['total_users']; ?></div>
    <div class="text-gray-500 text-sm">Friends Joined</div>
  </div>
</div>

<?php if ($isAdmin && $pendingCount > 0): ?>
  <div class="max-w-md mx-auto my-10">
    <a href="memories.php?sort=pending"
       class="block bg-yellow-50 border border-yellow-200 rounded-2xl p-6 text-center shadow hover:shadow-lg transition">
      <div class="text-3xl mb-2">⚠️</div>
      <div class="text-xl font-semibold text-yellow-800">
        <?php echo $pendingCount; ?> Pending Memories
      </div>
      <div class="text-sm text-yellow-600">Click to review and approve them</div>
    </a>
  </div>
<?php endif; ?>


<!-- 🎠 Carousel Slider -->
<div class="max-w-6xl mx-auto px-4 mb-20">
  <h2 class="text-3xl font-cursive text-center text-pink-600 mb-8">Featured Memories</h2>
  
  <div class="swiper mySwiper">
    <div class="swiper-wrapper">
      <?php while ($m = $carousel->fetch_assoc()): ?>
        <div class="swiper-slide">
          <a href="memory.php?id=<?php echo $m['id']; ?>"
             class="block bg-white rounded-2xl shadow hover:shadow-2xl overflow-hidden">
            <img src="<?php echo $m['image_path'] ?: 'default.jpg'; ?>"
                 class="w-full h-56 object-cover">
            <div class="p-4 text-center">
              <h3 class="font-semibold text-pink-600 truncate"><?php echo htmlspecialchars($m['title']); ?></h3>
              <p class="text-xs text-gray-500 mb-1">by <?php echo htmlspecialchars($m['display_name']); ?></p>
              <div class="flex justify-center items-center gap-1 text-sm text-pink-500">
                <i class="fa-solid fa-heart"></i> <?php echo $m['like_count']; ?>
              </div>
            </div>
          </a>
        </div>
      <?php endwhile; ?>
    </div>
    <!-- Arrows + Dots -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>
  </div>
</div>

<!-- Call to Action -->
<div class="text-center my-16">
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="add-memory.php" 
       class="bg-pink-600 text-white px-8 py-4 rounded-full shadow-lg hover:bg-pink-700 transition text-xl">
      🌸 Share Your Memory
    </a>
  <?php else: ?>
    <a href="register.php" 
       class="bg-pink-600 text-white px-8 py-4 rounded-full shadow-lg hover:bg-pink-700 transition text-xl">
      🌸 Join the Garden
    </a>
  <?php endif; ?>
</div>

<!-- 🌸 Animations -->
<!-- 🌸 Animations -->
<style>
/* Keyframes */
@keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
@keyframes slideUp { from{opacity:0; transform:translateY(30px);} to{opacity:1; transform:translateY(0);} }
@keyframes float { 0%,100%{transform:translateY(0) rotate(0deg);} 50%{transform:translateY(-15px) rotate(5deg);} }
@keyframes sparkle { 0%{opacity:0; transform:scale(0);} 50%{opacity:1; transform:scale(1);} 100%{opacity:0; transform:scale(0);} }
@keyframes pulseGlow { 0%,100%{box-shadow:0 0 10px rgba(236,72,153,0.4);} 50%{box-shadow:0 0 25px rgba(236,72,153,0.7);} }
@keyframes gradientFlow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Base animation classes */
.animate-fadeIn { animation: fadeIn 2s ease-out; }
.animate-slideUp { animation: slideUp 1.5s ease-out; }
.animate-float { animation: float 6s ease-in-out infinite; }

/* Flowers floating background */
.flower { transition: transform 0.4s ease-out; animation: float 8s ease-in-out infinite; }

/* Hero shimmer background */
.hero-bg {
  background: linear-gradient(-45deg, #ffe4ec, #ffffff, #fdf2f8, #ffe4ec);
  background-size: 300% 300%;
  animation: gradientFlow 15s ease infinite;
}

/* Destiny's photo pulse */
.hero-photo {
  animation: pulseGlow 3s ease-in-out infinite;
}

/* Sparkle effects */
.sparkle {
  position: absolute;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(236,72,153,0.7);
  animation: sparkle 2s ease-in-out infinite;
  pointer-events: none;
}

/* Hover glow on buttons/cards */
.glow-hover:hover {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 0 20px rgba(236,72,153,0.3);
  transition: all 0.3s ease;
}

/* Fade in stats one by one */
.stats div:nth-child(1) { animation: fadeIn 1s ease-out 0.2s both; }
.stats div:nth-child(2) { animation: fadeIn 1s ease-out 0.5s both; }
.stats div:nth-child(3) { animation: fadeIn 1s ease-out 0.8s both; }
</style>


<!-- Swiper.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
new Swiper(".mySwiper", {
  slidesPerView: 1,
  spaceBetween: 20,
  loop: true,
  autoplay: { delay: 3000, disableOnInteraction: false },
  breakpoints: {
    640: { slidesPerView: 2 },
    1024: { slidesPerView: 3 }
  },
  pagination: { el: ".swiper-pagination", clickable: true },
  navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
});

// 🌸 Parallax flowers
window.addEventListener('scroll', () => {
  const scrollY = window.scrollY;
  document.querySelectorAll('.flower').forEach((f, i) => {
    f.style.transform = `translateY(${scrollY * (0.1 + i*0.05)}px)`;
  });
});

// ✨ Sparkle generator
const sparkleContainer = document.getElementById('sparkles');
setInterval(() => {
  const s = document.createElement('div');
  s.className = 'sparkle';
  s.style.top = Math.random() * 200 + 'px';
  s.style.left = Math.random() * window.innerWidth + 'px';
  sparkleContainer.appendChild(s);
  setTimeout(() => s.remove(), 2000);
}, 300);
</script>
