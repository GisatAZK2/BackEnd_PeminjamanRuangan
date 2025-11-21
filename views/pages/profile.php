<?php
$user = json_decode($_COOKIE['user_info'] ?? '{}', true);
$nama = $user['nama'] ?? 'User';
$role = $user['role'] ?? 'peminjam';
?>

<div class="container mx-auto max-w-4xl px-4">

    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-sm p-8 mb-6 relative text-center">

    <img src="/public/person.svg"
     class="mx-auto w-8 h-8 rounded-full mb-3"
     alt="User Profile" />

        <h1 id="profile-name-display" class="text-2xl font-semibold text-gray-800">
            <?= htmlspecialchars($nama) ?>
        </h1>

        <span id="profile-role-display"
              class="inline-block mt-2 px-4 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium capitalize">
            <?= htmlspecialchars($role) ?>
        </span>
    </div>

    <!-- Profile Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Informasi Pribadi -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-5">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-user-circle mr-2 text-gray-500"></i> Informasi Pribadi
                </h2>
                <button onclick="ProfileController.edit()"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                    <i class="fas fa-pen text-xs"></i> Edit
                </button>
            </div>

            <div id="profile-details" class="space-y-3">
                <div class="animate-pulse space-y-3">
                    <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        </div>

        <!-- Keamanan -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-shield-alt mr-2 text-gray-500"></i> Keamanan
            </h2>

            <p class="text-gray-500 text-sm mb-5 leading-relaxed">
                Demi keamanan akun, disarankan mengganti password secara berkala.
            </p>

            <button onclick="ProfileController.changePassword()"
                    class="w-full bg-orange-50 text-orange-700 hover:bg-orange-100 font-semibold py-3 rounded-lg transition flex items-center justify-center gap-2">
                <i class="fas fa-key"></i> Ganti Password
            </button>
        </div>

    </div>
</div>

<script data-script-page src="/public/js/Pages/profile.js"></script>
