var DashboardController = {
    init: async () => {


        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => DashboardController.load());
        }

        await DashboardController.load();
    },

    load: async () => {
        const container = document.getElementById('stats-container');
        container.innerHTML = `
            ${[0,1,2,3].map(() => `
                <div class="bg-white rounded-xl shadow p-6 animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                    <div class="h-8 bg-gray-300 rounded w-1/4"></div>
                </div>
            `).join('')}
        `; // Skeleton loading

        try {
            const res = await Api.get('/statistik');
            if (res.status !== 'success') throw new Error(res.message || 'Gagal memuat statistik');

            const d = res.data;

            // Render Cards
            container.innerHTML = `
                ${DashboardController.card('Total Ruangan', d.total_ruangan, 'fa-door-open', 'blue')}
                ${DashboardController.card('Booking Hari Ini', d.today_bookings, 'fa-calendar-check', 'green')}
                ${DashboardController.card('Sedang Berlangsung', d.ongoing, 'fa-video', 'yellow')}
                ${DashboardController.card('Selesai Hari Ini', d.finished_today, 'fa-check-circle', 'purple')}
            `;

            // Render Chart jika bukan peminjam
            if (res.role !== 'peminjam' && d.peminjaman_per_hari?.length > 0) {
                DashboardController.renderChart(d.peminjaman_per_hari);
            } else {
                document.getElementById('chart-wrapper').classList.add('hidden');
            }

        } catch (err) {
            console.error(err);
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-exclamation-triangle text-5xl text-red-400 mb-4"></i>
                    <p class="text-red-600 font-medium">Gagal memuat data statistik</p>
                    <p class="text-gray-500 text-sm mt-2">${err.message || 'Silakan coba lagi'}</p>
                </div>
            `;
        }
    },

    card: (title, value, icon, color) => `
        <div class="bg-white rounded-xl shadow-md p-6 flex items-center justify-between hover:shadow-lg transition">
            <div>
                <p class="text-sm text-gray-600">${title}</p>
                <p class="text-3xl font-bold text-gray-800 mt-2">${value ?? 0}</p>
            </div>
            <div class="p-4 rounded-full bg-${color}-100 text-${color}-600">
                <i class="fas ${icon} text-2xl"></i>
            </div>
        </div>
    `,

    renderChart: (data) => {
        const wrapper = document.getElementById('chart-wrapper');
        wrapper.classList.remove('hidden');
        const ctx = document.getElementById('peminjamanChart').getContext('2d');

        // Hancurkan chart lama
        if (window.myChart instanceof Chart) {
            window.myChart.destroy();
        }

        window.myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(x => new Date(x.tanggal).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })),
                datasets: [{
                    label: 'Jumlah Peminjaman',
                    data: data.map(x => x.total),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }
};
DashboardController.init();