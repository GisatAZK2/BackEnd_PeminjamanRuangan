<?php
$user = json_decode($_COOKIE['user_info'] ?? '{}', true);
$nama = $user['nama'] ?? 'User';
$role = $user['role'] ?? 'peminjam';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-600">Halo, <span class="font-semibold"><?= htmlspecialchars($nama) ?></span> (<?= ucfirst($role) ?>)</p>
    </div>
   <button onclick="DashboardController.init()" class="btn-refresh" title="Refresh Data">
    <i class="fas fa-sync-alt text-xl"></i>
   </button>
</div>

<div id="stats-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php for($i=0;$i<4;$i++): ?>
    <div class="bg-white rounded-xl shadow p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
        <div class="h-8 bg-gray-300 rounded w-1/4"></div>
    </div>
    <?php endfor; ?>
</div>

<div id="chart-wrapper" class="bg-white rounded-xl shadow-md p-6 mb-8 hidden">
    <h2 class="text-xl font-semibold mb-4">Statistik 7 Hari Terakhir</h2>
    <div class="h-64"><canvas id="peminjamanChart"></canvas></div>
</div>

<script data-script-page src="/public/js/Pages/dashboard.js"></script>