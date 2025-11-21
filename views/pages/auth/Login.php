<?php
session_start();

if (isset($_COOKIE['user_info'])) {
    $user_info = json_decode($_COOKIE['user_info'], true);
    if ($user_info && isset($user_info['id_user'])) {
        header('Location: /dashboard');
        exit;
    }
}

?>

<body class="min-h-screen relative flex items-center justify-center p-4 overflow-hidden bg-gray-900">

  <!-- Background Video -->
  <video autoplay muted loop playsinline class="fixed top-0 left-0 w-full h-full object-cover -z-10">
    <source src="/public/video/BG-Login.mp4" type="video/mp4" />
  </video>
  <div class="fixed inset-0 bg-black/40 -z-10"></div>

  <!-- Login Card -->
  <div class="w-full max-w-md bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-8 border border-gray-100">
    <div class="text-center mb-6">
      <img src="/public/icon.svg" alt="Logo TNI AD" class="h-20 w-20 mx-auto mb-3" />
      <h1 class="text-2xl font-bold text-gray-800">PEMINJAMAN RUANG RAPAT</h1>
      <p class="text-sm text-gray-500">SUBDIS BINSISFOMIN</p>
    </div>

    <div class="flex items-center justify-center gap-3 mb-6 text-gray-600">
      <hr class="w-20 border-gray-300" />
      <span class="text-sm font-medium">SILAHKAN MASUK</span>
      <hr class="w-20 border-gray-300" />
    </div>

    <!-- Error Alert -->
    <div id="error-alert" class="hidden mb-5 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center gap-2">
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 112 0v3a1 1 0 11-2 0V9zm1 6a1 1 0 100-2 1 1 0 000 2z"/>
      </svg>
      <span id="error-message"></span>
    </div>

    <!-- Login Form -->
    <form id="loginForm" class="space-y-5">
      <!-- Username -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <input type="text" id="username" name="username" placeholder="admin01" required class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" />
        </div>
      </div>

      <!-- Password -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.1-.9-2-2-2s-2 .9-2 2m4 0c0-1.1-.9-2-2-2s-2 .9-2 2m4 0v4m-4-4v4m8-4v4m-4-8V7a4 4 0 00-8 0v4"/>
          </svg>
          <input type="password" id="password" name="password" placeholder="••••••••" required class="w-full pl-10 pr-12 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" />
          <button type="button" onclick="togglePassword(this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5 hidden show-pass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg class="w-5 h-5 hide-pass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.95 9.95 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="flex items-center justify-between">
        <label class="flex items-center text-sm text-gray-600 cursor-pointer">
          <input type="checkbox" id="remember" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
          Remember Me
        </label>
      </div>

      <!-- Submit Button -->
      <button type="submit" id="submitBtn" class="w-full py-3 rounded-lg font-semibold transition-all border-2 border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white active:scale-95 disabled:opacity-50">
        Sign In
      </button>
    </form>
  </div>

  <script src="/public/js/Pages/Login.js"></script>

</body>