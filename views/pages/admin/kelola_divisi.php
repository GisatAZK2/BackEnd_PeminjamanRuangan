<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Kelola Divisi</h1>
                    <p class="text-gray-500 text-sm mt-1">Daftar departemen/divisi perusahaan</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="DivisiController.load()" 
                          class="btn-refresh"
                            title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button onclick="DivisiController.add()" 
                            class="btn-add">
                        <i class="fas fa-plus"></i> Tambah Divisi
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Divisi -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-700 text-xs uppercase tracking-wider">
                        <tr class="bg-blue-100">
                            <th class="px-6 py-4 font-semibold">ID</th>
                            <th class="px-6 py-4 font-semibold">Nama Divisi</th>
                            <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="divisi-body" class="divide-y divide-gray-200 bg-white">
                        <!-- Row diisi oleh JS -->
                    </tbody>
                </table>
            </div>

            <!-- Loading State -->
            <div id="divisi-loading" class="p-16 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin-custom text-4xl mb-4"></i>
                <p class="text-lg">Memuat data divisi...</p>
            </div>

            <!-- Empty State -->
            <div id="divisi-empty" class="hidden p-20 text-center text-gray-400">
                <i class="fas fa-building text-8xl mb-6 opacity-40"></i>
                <p class="text-xl font-medium">Belum ada divisi terdaftar</p>
                <p class="text-sm mt-2">Klik tombol "Tambah Divisi" untuk menambahkan yang pertama</p>
            </div>
        </div>
    </div>
    
<script data-script-page src="/public/js/pages/admin/divisions.js"></script>