<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Kelola Ruang Rapat</h1>
                    <p class="text-gray-500 text-sm mt-1">Daftar ruangan yang tersedia</p>
                </div>

                <div class="flex items-center gap-3">

                    <button onclick="RoomController.load()" 
                    class="btn-refresh"
                            title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>

                    <button onclick="RoomController.add()"
                             class="btn-add">
                        <i class="fas fa-plus"></i> Tambah Ruangan
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden ">
            <div class="overflow-x-auto">
                <table class="border w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                        <tr class="bg-blue-100">
                            <th class="p-4 font-semibold">ID</th>
                            <th class="p-4 font-semibold">Nama Ruangan</th>
                            <th class="p-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="room-body" class="text-gray-700 divide-y divide-gray-100">
                     
                    </tbody>
                </table>
            </div>

            <!-- Loading State -->
            <div id="room-loading" class="p-12 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin-custom text-2xl"></i>
                <p class="mt-3">Memuat data ruangan...</p>
            </div>

            <!-- Empty State -->
            <div id="room-empty" class="hidden p-16 text-center text-gray-400">
                <i class="fas fa-inbox text- text-6xl mb-4 opacity-50"></i>
                <p class="text-lg font-medium">Belum ada ruangan terdaftar</p>
                <p class="text-sm mt-2">Klik tombol "Tambah Ruangan" untuk memulai</p>
            </div>
        </div>
    </div>
    
<script data-script-page src="/public/js/pages/admin/rooms.js"></script>