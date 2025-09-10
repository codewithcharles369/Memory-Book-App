<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="text-center py-20 relative overflow-hidden">

  <!-- Decorative floating flowers (soft animation) -->
  <div class="absolute top-10 left-10 text-pink-300 text-4xl animate-float">🌸</div>
  <div class="absolute bottom-20 right-16 text-pink-200 text-5xl animate-float-delayed">🌷</div>
  <div class="absolute top-1/3 left-1/3 text-pink-300 text-3xl animate-float">💮</div>

  <!-- Main Heading -->
  <h1 class="font-cursive text-5xl text-pink-600 mb-4 animate-fadeIn">
    Welcome to Cleopatra’s Memory Garden
  </h1>

  <!-- Subheading -->
  <p class="text-lg text-gray-700 max-w-xl mx-auto mb-8 animate-slideUp">
    A magical scrapbook where friends can share their sweetest memories, stories, and moments with Cleopatra 🌹✨
  </p>

  <!-- Action Buttons -->
  <div class="flex justify-center space-x-4 animate-slideUp">
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="add-memory.php" 
         class="bg-pink-600 text-white px-6 py-3 rounded-2xl shadow hover:bg-pink-700 transition">
        ➕ Add a Memory
      </a>
      <a href="memories.php" 
         class="bg-white text-pink-600 px-6 py-3 rounded-2xl shadow hover:bg-pink-50 transition">
        📖 View Memories
      </a>
    <?php else: ?>
      <a href="login.php" 
         class="bg-pink-600 text-white px-6 py-3 rounded-2xl shadow hover:bg-pink-700 transition">
        Login
      </a>
      <a href="register.php" 
         class="bg-white text-pink-600 px-6 py-3 rounded-2xl shadow hover:bg-pink-50 transition">
        Register
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Divider -->
<div class="text-center my-12">
  <span class="text-3xl">🌷🌸🌹</span>
</div>

<!-- Featured Memories -->
<div class="max-w-5xl mx-auto px-4">
  <h2 class="text-2xl font-cursive text-pink-600 mb-6 text-center">Latest Memories</h2>

  <?php
  // fetch 3 latest public memories
  $result = $conn->query("
    SELECT m.id, m.title, m.image_path, u.display_name, m.created_at
    FROM memories m
    JOIN users u ON m.user_id = u.id
    WHERE m.privacy = 'public'
    ORDER BY m.created_at DESC
    LIMIT 3
  ");
  ?>

  <?php if ($result && $result->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php $delay = 0; ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <a href="memory_detail.php?id=<?php echo $row['id']; ?>" 
        class="block bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden"
        data-aos="fade-up"
        data-aos-delay="<?php echo $delay; ?>">
        
        <?php if ($row['image_path']): ?>
            <img src="<?php echo $row['image_path']; ?>" 
                alt="Memory Image" 
                class="w-full h-40 object-cover">
        <?php else: ?>
            <div class="w-full h-40 bg-pink-50 flex items-center justify-center text-4xl text-pink-300">🌸</div>
        <?php endif; ?>
        
        <div class="p-4 text-center">
            <h3 class="text-lg font-semibold text-pink-600">
            <?php echo htmlspecialchars($row['title']); ?>
            </h3>
            <p class="text-sm text-gray-500">
            by <?php echo htmlspecialchars($row['display_name']); ?> <br>
            <?php echo date("M j, Y", strtotime($row['created_at'])); ?>
            </p>
        </div>
        </a>
        <?php $delay += 200; ?> <!-- staggered animations -->
    <?php endwhile; ?>
    </div>

  <?php else: ?>
    <p class="text-center text-gray-500">No memories yet. Be the first to share 🌹</p>
  <?php endif; ?>
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

<!-- Animations -->
<style>
@keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
@keyframes slideUp { from {transform: translateY(20px); opacity:0;} to {transform: translateY(0); opacity:1;} }
@keyframes float { 0% { transform: translateY(0);} 50% { transform: translateY(-15px);} 100% { transform: translateY(0);} }

.animate-fadeIn { animation: fadeIn 2s ease-out; }
.animate-slideUp { animation: slideUp 1.5s ease-out; }
.animate-float { animation: float 6s ease-in-out infinite; }
.animate-float-delayed { animation: float 8s ease-in-out infinite; animation-delay: 2s; }
</style>

<?php include 'includes/footer.php'; ?>
