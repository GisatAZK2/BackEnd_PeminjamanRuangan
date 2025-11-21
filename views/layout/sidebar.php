<?php
if (!isset($_COOKIE['user_info'])) {
    header('Location: /');
    exit;
}
$rawCookie = $_COOKIE['user_info'] ?? '';
$userJson  = urldecode($rawCookie);
$user      = json_decode($userJson, true) ?: [];
$userRole = $user['role'] ?? 'peminjam';

$menuItems = [
    ['halaman' => 'dashboard',          'label' => 'Dashboard',         'icon' => 'fas fa-tachometer-alt', 'roles' => ['administrator', 'petugas', 'peminjam']],
    ['halaman' => 'admin/kelola_users',   'label' => 'Kelola User',         'icon' => 'fas fa-users-cog',      'roles' => ['administrator', 'petugas']],
    ['halaman' => 'admin/kelola_rooms',   'label' => 'Kelola Ruang Rapat',  'icon' => 'fas fa-door-open',      'roles' => ['administrator']],
    ['halaman' => 'admin/kelola_divisi',  'label' => 'Kelola Divisi',         'icon' => 'fas fa-building',       'roles' => ['administrator', 'petugas']],
    ['halaman' => 'history',            'label' => 'Kelola Peminjaman',  'icon' => 'fas fa-history',        'roles' => ['administrator', 'petugas', 'peminjam']],
    ['halaman' => 'booking',         'label' => 'Ajukan Peminjaman',   'icon' => 'fas fa-calendar-plus',  'roles' => ['peminjam']],
    ['halaman' => 'notulen',         'label' => 'Notulen Rapat',   'icon' => 'fas fa-file-alt',  'roles' => ['peminjam']],
];

$filteredMenu   = array_filter($menuItems, fn($item) => in_array($userRole, $item['roles']));

?>

<aside id="sidebar"
        class="fixed top-0 left-0 h-full bg-[#0F1A2B] text-white z-40 mt-16 w-64 transition-all duration-300"
        style="transition-property: transform, width;">
    <div class="flex flex-col h-full">
        <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
            <?php foreach ($filteredMenu as $item):
                $page   = $item['halaman'];
                $active = false; 
                ?>
                <a href="#"
                    data-spa="true"
                    data-page="<?= htmlspecialchars($page) ?>"
                    class="sidebar-link group flex items-center gap-3 px-3 py-2 rounded-md relative transition-all duration-200 cursor-pointer
                            <?= $active ? 'bg-[#1F2D3D] text-white' : 'text-gray-300 hover:bg-[#1A2435]' ?>">
                    <span class="absolute left-0 top-0 h-full w-1 rounded-r-md transition-all duration-200
                                 <?= $active ? 'bg-blue-100' : 'group-hover:bg-blue-100' ?>"></span>
                    <i class="<?= $item['icon'] ?> text-lg"></i>
                    <span class="menu-label"><?= htmlspecialchars($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-gray-700">
            <a href="/logout.php" class="flex items-center gap-3 text-red-400 hover:text-red-300">
                <i class="fas fa-sign-out-alt"></i>
                <span class="menu-label">Logout</span>
            </a>
        </div>
    </div>

</aside>

