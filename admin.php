<?php
include 'includes/header.php';
include 'config/db.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: memories.php");
    exit;
}
?>

<div class="max-w-5xl mx-auto">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6 text-center">👑 Cleopatra’s Garden Admin</h1>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Manage Users -->
    <a href="admin_users.php" class="block bg-white rounded-xl shadow hover:shadow-lg p-6 text-center">
      <h2 class="text-xl font-semibold text-pink-600">👥 Manage Users</h2>
      <p class="text-gray-600">View or remove users</p>
    </a>

    <!-- Manage Memories -->
    <a href="admin_memories.php" class="block bg-white rounded-xl shadow hover:shadow-lg p-6 text-center">
      <h2 class="text-xl font-semibold text-pink-600">📸 Manage Memories</h2>
      <p class="text-gray-600">Review or delete memories</p>
    </a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
