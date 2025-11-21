<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        
        <!-- Header Card dengan Refresh di KANAN JUDUL (samping-samping) -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Riwayat Peminjaman Ruang Rapat</h1>
                    <p class="text-gray-500 text-sm mt-1">Pantau & kelola semua pengajuan peminjaman</p>
                </div>

                <!-- Tombol Refresh - SEKARANG BENAR DI SEBELAH KANAN JUDUL -->
                <button id="refresh-btn" class="btn-refresh hover:scale-110 transition-all duration-200" title="Refresh Data">
                    <i class="fas fa-sync-alt text-gray-600 hover:text-blue-600 text-2xl"></i>
                </button>
            </div>

            <!-- Filter tetap di bawah, full width -->
            <div class="mt-6 flex justify-end">
                <div class="bg-gray-100 p-2 rounded-xl flex flex-wrap gap-2 shadow-inner">
                    <button data-filter="semua" class="filter-btn active px-6 py-3 rounded-lg font-medium text-sm flex items-center gap-2 transition-all">
                        <i class="fas fa-list-ul text-xs opacity-70"></i> Semua
                    </button>
                    <button data-filter="pending" class="filter-btn px-6 py-3 rounded-lg font-medium text-sm flex items-center gap-2 transition-all">
                        <i class="fas fa-clock text-xs opacity-70"></i> Pending
                    </button>
                    <button data-filter="disetujui" class="filter-btn px-6 py-3 rounded-lg font-medium text-sm flex items-center gap-2 transition-all">
                        <i class="fas fa-check-circle text-xs opacity-70"></i> Disetujui
                    </button>
                    <button data-filter="ditolak" class="filter-btn px-6 py-3 rounded-lg font-medium text-sm flex items-center gap-2 transition-all">
                        <i class="fas fa-times-circle text-xs opacity-70"></i> Ditolak
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-300">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gradient-to-r from-blue-50 to-blue-100 text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 border-r border-gray-300">User / Ruangan</th>
                            <th class="px-6 py-4 border-r border-gray-300">Kegiatan</th>
                            <th class="px-6 py-4 border-r border-gray-300">Tanggal & Jam</th>
                            <th class="px-6 py-4 border-r border-gray-300 text-center">Status</th>
                            <th class="px-6 py-4 border-r border-gray-300 text-center">Aksi</th>
                            <th class="px-6 py-4">Notulen</th>
                        </tr>
                    </thead>
                    <tbody id="history-body" class="divide-y divide-gray-200 text? text-sm text-gray-700 bg-white">
                        <!-- Data diisi oleh JS -->
                    </tbody>
                </table>
            </div>

          
            <!-- Loading State -->
            <div id="history-loading" class="p-16 text-center text-gray-500 border-t border-gray-300">
                <i class="fas fa-spinner fa-spin-custom"></i>
                <p class="font-medium text-lg">Memuat riwayat...</p>
            </div>

            <!-- Empty -->
            <div id="history-empty" class="hidden p-20 text-center text-gray-400 border-t border-gray-300">
                <i class="text-8xl mb-6 opacity-40"></i>
                <p class="text-2xl font-semibold">Tidak ada riwayat peminjaman</p>
                <p class="text-sm mt-3">Coba ubah filter atau tunggu pengajuan baru</p>
            </div>
        </div>
    </div>

    <script data-script-page src="/public/js/Pages/history.js"></script>
</body>