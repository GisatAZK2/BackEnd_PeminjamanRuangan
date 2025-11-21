<?php
if (isset($currentPath) && $currentPath === '/login') return;
$user = null;
if (isset($_COOKIE['user_info'])) {
    $user = json_decode($_COOKIE['user_info'], true);
}
if (!$user) {
    header('Location: /'); exit;
}
?>

<header class="fixed top-0 left-0 right-0 bg-gray-900 text-white shadow-lg z-50 h-16">
  <div class="flex items-center justify-between px-4 py-3 h-full">
    <div class="flex items-center gap-3">
      <button onclick="toggleMobileSidebar()" class="lg:hidden">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <button onclick="toggleSidebar()" class="hidden lg:block">
        <i id="sidebarToggleIcon" class="fas fa-compress-arrows-alt text-lg"></i>
      </button>
      <a href="/dashboard" class="flex items-center gap-2">
        <img src="/public/icon.svg" alt="Logo" class="h-8 w-auto" />
        <h1 class="text-lg font-bold hidden sm:block">PEMINJAMAN</h1>
      </a>
    </div>

 <div class="relative z-[9999]">
  <button id="userDropdownBtn"
    class="flex items-center gap-2 p-1 rounded-full hover:bg-gray-800 focus:outline-none">
    <img src="/public/person.svg" alt="User" class="w-9 h-9 rounded-full ring-2 object-cover" />
    <div class="hidden md:block text-left">
      <div class="text-sm font-semibold"><?= htmlspecialchars($user['nama'] ?? 'User') ?></div>
      <div class="text-xs text-gray-400 capitalize"><?= htmlspecialchars($user['role'] ?? '') ?></div>
    </div>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
  </button>

  <div id="userDropdown"
    class="hidden absolute right-0 mt-2 w-64 rounded-xl shadow-2xl overflow-hidden bg-white z-[99999]">

    <div class="px-4 py-3 bg-blue-600 text-white">
      <div class="flex items-center gap-3">
        <img src="/public/person.svg" alt="User" class="w-9 h-9 rounded-full ring-2 ring-green-500 object-cover" />
        <div>
          <div class="font-semibold text-sm"><?= htmlspecialchars($user['nama'] ?? '') ?></div>
          <div class="text-xs opacity-90 capitalize"><?= htmlspecialchars($user['role'] ?? '') ?></div>
        </div>
      </div>
    </div>

    <a href="?halaman=profile" class="flex items-center gap-3 px-4 py-2 hover:bg-gray-100 text-gray-800">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
      Profil Saya
    </a>

    <button onclick="logout()" class="w-full text-left flex items-center gap-3 px-4 py-2 text-red-600 hover:bg-red-50">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Logout
    </button>
  </div>
</div>

</header>