<?php
include __DIR__ . '/../config/db.php'; // always load database + isAdmin

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cleopatra’s Memory Garden</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
 <!-- AOS Library -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<style>
    body {
      font-family: 'Inter', sans-serif;
    }
    .font-cursive {
      font-family: 'Great Vibes', cursive;
    }
  </style>
</head>
<body class="bg-gradient-to-b from-pink-50 via-pink-100 to-white text-gray-800 min-h-screen flex flex-col">

  <!-- Navigation -->
  <nav class="bg-white/80 backdrop-blur-md shadow p-4 sticky top-0 z-50">
    <div class="container mx-auto flex justify-between items-center">
      <!-- Logo -->
      <a href="index.php" class="font-cursive text-2xl md:text-3xl text-pink-600">
        🌸 Cleopatra’s Memory Garden
      </a>

      <!-- Desktop Links -->
      <div class="hidden md:flex space-x-4">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="index.php" class="hover:text-pink-600">Home</a>
          <a href="add-memory.php" class="hover:text-pink-600">Add Memory</a>
          <a href="memories.php" class="hover:text-pink-600">View Memories</a>
          <a href="logout.php" class="hover:text-pink-600">Logout</a>
        <?php else: ?>
          <a href="login.php" class="hover:text-pink-600">Login</a>
          <a href="register.php" class="hover:text-pink-600">Register</a>
        <?php endif; ?>
      </div>

      <!-- Mobile Hamburger -->
      <div class="md:hidden">
        <button id="menu-toggle" class="text-pink-600 focus:outline-none text-2xl">
          ☰
        </button>
      </div>
    </div>

    <!-- Mobile Dropdown -->
    <div id="mobile-menu" class="hidden md:hidden mt-3 space-y-2">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="index.php" class="block px-2 py-1 rounded hover:bg-pink-100">Home</a>
        <a href="add-memory.php" class="block px-2 py-1 rounded hover:bg-pink-100">Add Memory</a>
        <a href="memories.php" class="block px-2 py-1 rounded hover:bg-pink-100">View Memories</a>
        <a href="logout.php" class="block px-2 py-1 rounded hover:bg-pink-100">Logout</a>
      <?php else: ?>
        <a href="login.php" class="block px-2 py-1 rounded hover:bg-pink-100">Login</a>
        <a href="register.php" class="block px-2 py-1 rounded hover:bg-pink-100">Register</a>
      <?php endif; ?>
    </div>
  </nav>

  <script>
    // Mobile menu toggle
    document.addEventListener("DOMContentLoaded", () => {
      const toggle = document.getElementById("menu-toggle");
      const menu = document.getElementById("mobile-menu");

      toggle.addEventListener("click", () => {
        menu.classList.toggle("hidden");
      });
    });
  </script>


  <main class="container mx-auto flex-1 mt-6">
