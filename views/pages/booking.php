<body class="bg-gray-50 min-h-screen">
  <div class="container mx-auto px-4 py-8 max-w-5xl">

    <!-- HEADER CARD DENGAN TOMBOL REFRESH DI KANAN ATAS -->
    <div class="bg-white rounded-xl shadow-sm p-8 mb-8 border border-gray-100 relative overflow-hidden">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">Booking Ruang Rapat</h1>
          <p class="text-gray-600 mt-2">Ajukan peminjaman ruang rapat (bisa lebih dari 1 hari)</p>
        </div>

        <!-- TOMBOL REFRESH (pojok kanan atas) -->
        <button onclick="BookingController.refreshAll()" 
                id="refresh-calendar-btn"
                class="btn-refresh"
                title="Refresh Ketersediaan Ruangan">
          <i class="fas fa-sync-alt text-lg"></i>
        </button>
      </div>
    </div>

    <!-- FORM BOOKING -->
    <section class="bg-white shadow-lg rounded-2xl border p-8">
      <h2 class="text-xl font-bold text-gray-800 mb-6">Form Peminjaman</h2>
     
      <div class="grid md:grid-cols-2 gap-8">
        <!-- Form Kiri -->
        <div class="space-y-5">
          <div>
            <label class="block font-semibold mb-2">Ruangan</label>
            <select id="b-room" class="w-full border rounded-lg px-4 py-3 text-gray-700"></select>
          </div>
          <div>
            <label class="block font-semibold mb-2">Kegiatan / Acara</label>
            <input id="b-activity" class="w-full border rounded-lg px-4 py-3" placeholder="Contoh: Workshop 3 hari">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold mb-2">Tanggal Mulai</label>
              <input id="b-start-date" type="date" class="w-full border rounded-lg px-4 py-3" readonly>
            </div>
            <div>
              <label class="block font-semibold mb-2">Tanggal Selesai</label>
              <input id="b-end-date" type="date" class="w-full border rounded-lg px-4 py-3" readonly>
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-2">Jam Mulai & Selesai (per hari)</label>
            <div class="flex items-center gap-3">
              <input id="b-start" type="time" class="border rounded-lg px-4 py-3" value="09:00">
              <span class="text-gray-600">s/d</span>
              <input id="b-end" type="time" class="border rounded-lg px-4 py-3" value="17:00">
            </div>
          </div>
        </div>

        <!-- Kalender Kanan -->
        <div>
          <div class="cal-nav mb-4">
            <button onclick="BookingController.prevMonth()" class="text-2xl">&lt;</button>
            <div class="cal-header font-bold text-lg" id="cal-title"></div>
            <button onclick="BookingController.nextMonth()" class="text-2xl">&gt;</button>
          </div>
          <div id="calendar-placeholder"></div>
    
        </div>
      </div>

      <div class="mt-8 text-right">
        <button onclick="BookingController.submitForm()"
          class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition">
          Kirim Pengajuan
        </button>
      </div>
    </section>
  </div>

  <script data-script-page src="/public/js/Pages/booking.js"></script>
</body>
</html>