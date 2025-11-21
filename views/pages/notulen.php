<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen">
  <div class="container mx-auto px-4 py-10 max-w-5xl">

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm p-8 mb-8 border border-gray-100">
      <h1 class="text-3xl font-bold text-gray-800">Selesaikan Rapat & Upload Notulen</h1>
      <p class="text-gray-600 mt-2">Pilih booking yang sudah selesai lalu unggah notulen (bisa lebih dari 1 file)</p>
    </div>

    <!-- Main Card -->
    <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

      <div class="p-8 lg:p-10 border-b border-gray-200">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
            <i class="fas fa-calendar-alt text-xl"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-800">Pilih Booking Rapat</h2>
        </div>

        <div class="relative">
          <select id="booking-select" class="w-full appearance-none bg-gray-50 border-2 border-gray-300 rounded-xl px-5 py-4 pr-12 text-gray-700 text-lg font-medium focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 transition-all cursor-pointer">
            <option value="">-- Pilih booking yang sudah selesai --</option>
          </select>
        </div>

        <!-- Booking Info Card (Hidden by default) -->
        <div id="booking-info" class="hidden mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
            <div class="flex items-center gap-3">
              <i class="fas fa-door-open text-blue-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-500">Ruangan</p>
                <p id="info-ruangan" class="font-semibold text-lg"></p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <i class="fas fa-tasks text-indigo-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-500">Kegiatan</p>
                <p id="info-kegiatan" class="font-semibold text-lg"></p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <i class="fas fa-calendar-day text-green-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-500">Tanggal</p>
                <p id="info-tanggal" class="font-semibold"></p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <i class="fas fa-clock text-purple-600 text-xl"></i>
              <div>
                <p class="text-sm text-gray-500">Jam</p>
                <p id="info-jam" class="font-semibold"></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-8 lg:p-10 bg-gray-50">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
            <i class="fas fa-file-upload text-xl"></i>
          </div>
          <h2 class="text-2xl font-bold text-gray-800">Upload Notulen Rapat</h2>
        </div>

        <!-- Drag & Drop Area -->
        <label for="files-input" class="block">
          <div class="border-3 border-dashed border-gray-300 rounded-2xl p-10 text-center hover:border-blue-400 hover:bg-blue-50 transition-all cursor-pointer group">
            
            <i class="fas fa-cloud-upload-alt  text-gray-400 group-hover:text-blue-500 transition"></i>
            <p class="mt-4 text-xl font-semibold text-gray-700">Klik untuk memilih file atau drag & drop</p>
            <p class="text-sm text-gray-500 mt-2">PDF, Word, JPG, PNG • Maks 16MB per file</p>
          </div>
        </label>
        <input type="file" id="files-input" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" class="hidden">

        <!-- File Count -->
        <p class="text-center mt-4 text-gray-600">
          File terpilih: <span id="file-count" class="font-bold text-blue-600">0 file</span>
        </p>

        <!-- Preview Grid -->
        <div id="preview-container" class="mt-8 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4"></div>

        <!-- Upload Button -->
        <div class="mt-10 text-center">
          <button id="upload-btn" disabled onclick="NotulenController.upload()"
            class="px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-lg rounded-2xl shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center gap-3 mx-auto">
            <i class="fas fa-check-circle text-2xl"></i>
            Selesaikan Rapat & Upload Notulen
          </button>
          <p class="text-xs text-gray-500 mt-4">Pastikan semua file sudah benar sebelum mengirim</p>
        </div>
      </div>
    </div>

  </div>

  <script data-script-page src="/public/js/Pages/notulen.js"></script>