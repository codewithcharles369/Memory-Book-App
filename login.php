<?php
include 'includes/header.php';
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, display_name, password_hash, role FROM users WHERE display_name = ?");
    $stmt->bind_param("s", $display_name);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['role'] = $user['role']; // 👈 important

            header("Location: index.php");
            exit;
        } else {
            $err = "Wrong password. Try again 💔.";
        }
    } else {
        $err = "No account with that name.";
    }
}
?>

<div class="max-w-md mx-auto bg-white p-6 rounded-2xl shadow">
  <h1 class="text-2xl font-cursive text-pink-600 mb-4">Welcome back 🌷</h1>

  <?php if (!empty($err)): ?>
    <p class="bg-red-100 text-red-600 p-2 rounded mb-4"><?php echo $err; ?></p>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-4">
      <label class="block mb-1 font-medium">Your Name</label>
      <input type="text" name="display_name" class="w-full border rounded-lg p-2" required>
    </div>
    <div class="mb-4">
      <label class="block mb-1 font-medium">Password</label>
      <input type="password" name="password" class="w-full border rounded-lg p-2" required>
    </div>
    <button type="submit" class="bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700">
      Login
    </button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>
