<?php
// Sprawdź, czy plik obrazu użytkownika istnieje
$imagePath = "media/user_images/{$_SESSION['username']}.jpg";

// Jeśli plik obrazu użytkownika nie istnieje, ustaw domyślny obraz
if (!file_exists($imagePath)) {
    $imagePath = "media/user_images/default.jpg"; // Ścieżka do domyślnego obrazu
}
?>
<header class="fixed top-0 left-0 w-full bg-white bg-opacity-60 backdrop-blur-sm border-b border-gray-200 z-50">
  <section class="container mx-auto px-6 sm:px-0 md:px-2 lg:px-2 py-4">
    <div class="flex flex-row items-center justify-between">
      <div class="flex items-center space-x-4">
        <a href="index.php" class="flex items-center font-medium text-gray-900">
          <span class="text-xl font-black leading-none select-none" style="font-family: VT323, serif;">
            my<span class="text-indigo-600">Cloud</span>
          </span>
        </a>

        <!-- Separator line between logo and menu (only for desktop) -->
        <div class="hidden md:block h-6 border-l border-gray-300"></div>

        <!-- Desktop menu -->
        <nav class="hidden md:flex items-center space-x-6">
          <a href="index.php" class="text-base font-medium text-gray-600 hover:text-gray-900">Strona główna</a>
          <a href="storage.php" class="text-base font-medium text-gray-600 hover:text-gray-900">Dysk</a>
          <a href="login_attempts.php" class="text-base font-medium text-gray-600 hover:text-gray-900">Historia logowań</a>
          <a href="akcje.php" class="text-base font-medium text-gray-600 hover:text-gray-900">Akcje</a>
          <a href="chat.php" class="text-base font-medium text-gray-600 hover:text-gray-900">Chat</a>
        </nav>
      </div>

      <div id="user-icon-container" class="flex items-center justify-center cursor-pointer text-gray-600 hover:text-gray-900 hidden md:block lg:block relative"> 
  
  <div id="user-icon" class="flex items-center justify-center relative"> <!-- Dodatkowy kontener -->

    <svg id="user-arrow" class="w-4 h-4 transition-transform duration-300 mr-2 ease-in-out" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
    </svg>
    <img src="<?php echo $imagePath; ?>" alt="User" class="w-8 h-8 rounded-full border border-gray-300">
  </div>
  
  <!-- Menu rozwijane -->
  <div id="user-dropdown" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg hidden z-60">
    <a href="settings.php" class="block px-4 py-2 text-gray-600 hover:text-gray-900">Ustawienia</a>
    <a href="wyloguj.php" class="block px-4 py-2 text-gray-600 hover:text-gray-900">Wyloguj</a>
  </div>
</div>


      <!-- Przycisk hamburgera na urządzenia mobilne -->
      <button id="hamburger-btn" class="md:hidden text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>
  </section>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="fixed top-0 right-0 w-64 h-100vh bg-white shadow-lg z-50 transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
    <!-- Close Button -->
    <div class="flex justify-end p-4">
      <button id="close-menu-btn" class="text-gray-900 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <div class="flex flex-col p-6 space-y-4">
      <a href="index.php" class="text-lg font-medium text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">Strona główna</a>
      <a href="login_attempts.php" class="text-lg font-medium text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">Historia logowań</a>
      <a href="akcje.php" class="text-lg font-medium text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">Akcje</a>
      <a href="chat.php" class="text-lg font-medium text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">Chat</a>
      <a href="settings.php" class="text-lg font-medium text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">Ustawienia</a>
      <a href="wyloguj.php" class="text-lg font-medium text-gray-600 hover:text-gray-900" onclick="toggleMobileMenu()">Wyloguj</a>
    </div>
  </div>
</header>

<div class="pt-6 md:pt-10 lg:pt-10"></div>

<!-- JavaScript for toggling menus and user dropdown -->
<script>



  // Elements
  const hamburgerBtn = document.getElementById('hamburger-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  const closeMenuBtn = document.getElementById('close-menu-btn');


  // Toggle mobile menu visibility
  function toggleMobileMenu() {
    mobileMenu.classList.toggle('translate-x-full');
  }

  // Show mobile menu when hamburger button is clicked
  hamburgerBtn.addEventListener('click', () => {
    mobileMenu.classList.remove('translate-x-full');
  });

  // Close mobile menu when close button is clicked
  closeMenuBtn.addEventListener('click', () => {
    mobileMenu.classList.add('translate-x-full');
  });

// Elements
const userIcon = document.getElementById('user-icon');
const userDropdown = document.getElementById('user-dropdown');
const userArrow = document.getElementById('user-arrow');

// Show user dropdown menu on click
userIcon.addEventListener('click', () => {
  // Toggle the visibility of the dropdown menu
  userDropdown.classList.toggle('hidden');
  
  userArrow.classList.toggle('rotate-180');
});


  // Change navbar background color when scrolled
  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('header');
    if (window.scrollY > 10) {
      navbar.classList.add('bg-white', 'shadow-md');
      navbar.classList.remove('bg-transparent');
    } else {
      navbar.classList.remove('shadow-md');
      navbar.classList.add('bg-transparent');
    }
  });
</script>
