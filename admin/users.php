<?php
include '../config/db.php';
include '../includes/header.php';

// Ensure only admins can access
if (!isAdmin()) {
    header("Location: ../memories.php");
    exit;
}

// Handle search query
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE display_name LIKE CONCAT('%', ?, '%') ORDER BY id DESC");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
}
?>


  <!-- ✅ Feedback Banners -->
  <?php if (isset($_GET['msg']) || isset($_GET['err'])): ?>
    <?php
      $message = '';
      $classes = '';

      if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
          $message = "User deleted successfully 🗑️";
          $classes = "bg-red-100 text-red-700";
      } elseif (isset($_GET['msg']) && $_GET['msg'] === 'reset') {
          $message = "Password reset to default (password123) 🔑";
          $classes = "bg-yellow-100 text-yellow-700";
      } elseif (isset($_GET['err']) && $_GET['err'] === 'cannotdelete') {
          $message = "You cannot delete this user ⚠️";
          $classes = "bg-red-100 text-red-700";
      } elseif (isset($_GET['err']) && $_GET['err'] === 'cannotreset') {
          $message = "You cannot reset this user’s password ⚠️";
          $classes = "bg-yellow-100 text-yellow-700";
      } elseif (isset($_GET['err']) && $_GET['err'] === 'invalid') {
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
    // Auto-hide flash messages after 3 seconds
    document.addEventListener("DOMContentLoaded", () => {
      const flash = document.getElementById("flash-message");
      if (flash) {
        setTimeout(() => {
          flash.classList.add("opacity-0");
          setTimeout(() => flash.remove(), 1000); // remove after fade
        }, 3000);
      }
    });
  </script>




<div class="max-w-5xl mx-auto">
  <h1 class="text-3xl font-cursive text-pink-600 mb-6 text-center">👥 Manage Users</h1>

<div class="flex justify-between items-center mb-4">
  <form method="GET" class="flex space-x-2">
    <input type="text" name="search" 
           value="<?php echo htmlspecialchars($search); ?>" 
           placeholder="Search by name..." 
           class="border rounded-lg px-3 py-2 w-64">
    <button type="submit" 
            class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition">
      Search
    </button>
  </form>

  <?php if ($search): ?>
    <a href="users.php" class="text-sm text-gray-500 hover:text-gray-700">Clear Search ✖</a>
  <?php endif; ?>
</div>


  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <table class="min-w-full text-left border-collapse">
        <thead class="bg-pink-100 text-pink-700">
          <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2">Role</th>
            <th class="px-4 py-2">Joined</th>
            <th class="px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($user = $result->fetch_assoc()): ?>
            <tr class="border-t">
              <td class="px-4 py-2"><?php echo $user['id']; ?></td>
              <td class="px-4 py-2"><?php echo htmlspecialchars($user['display_name']); ?></td>
              <td class="px-4 py-2">
                <?php if ($user['role'] === 'admin'): ?>
                  <span class="bg-pink-200 text-pink-700 px-2 py-1 rounded-full text-xs">Admin</span>
                <?php else: ?>
                  <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded-full text-xs">User</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2 text-sm text-gray-600"><?php echo date("M j, Y", strtotime($user['created_at'])); ?></td>
              <td class="px-4 py-2 space-x-2">
                <?php if ($user['role'] !== 'admin'): ?>
                  <!-- Delete User -->
                  <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                     onclick="return confirm('Are you sure you want to delete this user?')" 
                     class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">Delete</a>
                  
                  <!-- Reset Password -->
                  <a href="reset_user.php?id=<?php echo $user['id']; ?>" 
                     onclick="return confirm('Reset this user\'s password to default?')" 
                     class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-sm">Reset</a>
                <?php else: ?>
                  <span class="text-gray-400 text-sm italic">Protected</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-center text-gray-600">No users found.</p>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
