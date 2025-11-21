<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center">
                <!-- Judul dan deskripsi -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manajemen User</h1>
                    <p class="text-gray-500 text-sm mt-1">Kelola akun pengguna, role, dan divisi.</p>
                </div>
            <div class="flex  items-center gap-3">
              <button onclick="UserController.loadData()" class="btn-refresh">
                 <i class="fas fa-sync-alt"></i>
              </button>
                    <button onclick="UserController.add()" 
                            class="btn-add">
                        <i class="fas fa-plus"></i> Tambah User
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel User -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full border text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                        <tr class="bg-blue-100">
                            <th class="p-4 font-semibold">User Info</th>
                            <th class="p-4 font-semibold">Role</th>
                            <th class="p-4 font-semibold">Divisi</th>
                            <th class="p-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body" class="text-gray-700 divide-y divide-gray-100">
                    </tbody>
                </table>
            </div>

            <!-- Loading State -->
             <div id="user-loading" class="p-12 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin-custom text-2xl"></i>
                <p class="mt-3">Memuat data ruangan...</p>
            </div>

            <!-- Empty State -->
            <div id="user-empty" class="hidden p-16 text-center text-gray-400">
                <i class="fas fa-users text-6xl mb-4 opacity-50"></i>
                <p class="text-lg font-medium">Belum ada user terdaftar</p>
                <p class="text-sm mt-2">Klik tombol "Tambah User" untuk memulai</p>
            </div>

    </div>

    <script data-script-page src="/public/js/Pages/admin/users.js"></script>
</body>