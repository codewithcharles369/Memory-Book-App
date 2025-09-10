  </main>
<script>
  AOS.init({
    duration: 1000, // animation duration (ms)
    once: true      // run only once
  });
</script>

<?php if (function_exists('isAdmin') && isAdmin()): ?>
  <a href="admin/index.php" 
     class="fixed bottom-20 right-6 bg-pink-600 text-white p-4 rounded-full shadow-lg hover:bg-pink-700 transition transform hover:scale-110"
     title="Admin Dashboard">
    👑
  </a>
<?php endif; ?>

  <!-- Footer -->
  <footer class="bg-white shadow-inner mt-10">
    <div class="container mx-auto px-4 py-6 text-center text-gray-500 text-sm">
      Made with 💖 for Cleopatra • <span class="cursive text-pink-500">Memories that last forever</span>
    </div>
  </footer>
</body>
</html>
