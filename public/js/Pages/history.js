var HistoryController = {
    currentFilter: 'semua',

    getUserRole: () => {
        try {
            const cookie = document.cookie
                .split('; ')
                .find(row => row.startsWith('user_info='));
            if (!cookie) return null;
            const userInfo = JSON.parse(decodeURIComponent(cookie.split('=')[1]));
            return userInfo.role?.toLowerCase(); 
        } catch (e) {
            console.error('Gagal membaca cookie user_info', e);
            return null;
        }
    },

    isPetugas: () => {
        const role = HistoryController.getUserRole();
        return role === 'petugas';
    },

    init: () => {
        document.getElementById('refresh-btn')?.addEventListener('click', () => {
            HistoryController.load(HistoryController.currentFilter);
        });

        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-filter]').forEach(b =>
                    b.classList.remove('bg-blue-600', 'text-white')
                );
                btn.classList.add('bg-blue-600', 'text-white');
                HistoryController.currentFilter = btn.dataset.filter;
                HistoryController.load(btn.dataset.filter);
            });
        });

        document.querySelector('[data-filter="semua"]')?.classList.add('bg-blue-600', 'text-white');
        HistoryController.load('semua');
    },

    load: async (filter = 'semua') => {
        const tbody   = document.getElementById('history-body');
        const loading = document.getElementById('history-loading');

        tbody.innerHTML = '';
        loading.classList.remove('hidden');

        try {
            const response = await Api.get(`/GetHistory?filter=${filter}`);
            console.log('%c[DATA DARI SERVER]', 'color: lime; font-weight: bold;', response);

            Notify.close();
            loading.classList.add('hidden');

            let data = [];
            if (Array.isArray(response)) data = response;
            else if (response && Array.isArray(response.data)) data = response.data;
            else if (response && Array.isArray(response.result)) data = response.result;
            else data = [];

            if (data.length === 0) {
                document.getElementById('history-empty')?.classList.remove('hidden');
                return;
            } else {
                document.getElementById('history-empty')?.classList.add('hidden');
            }

            data.forEach(item => tbody.innerHTML += HistoryController.row(item));

        } catch (err) {
            Notify.close();
            loading.classList.add('hidden');
            Notify.error(err.message || 'Gagal memuat data dari server');
            console.error(err);
        }
    },

    approve: async (id, namaUser = '') => {
        if (!HistoryController.isPetugas()) return Notify.error('Aksi ini hanya untuk Petugas');

        const keterangan = await Notify.promptText(
            `Setujui booking atas nama ${namaUser || 'user'}?`,
            'Keterangan (opsional)',
            'Ruang tersedia'
        );
        if (keterangan === null) return;

        const confirmed = await Notify.confirm('Yakin menyetujui booking ini?', 'Aksi ini tidak dapat dibatalkan.', 'Ya, Setujui');
        if (!confirmed) return;

        Notify.loading('Menyetujui booking...');
        try {
            await Api.post(`/UpdateStatusBooking/${id}`, {
                status: "disetujui",
                keterangan: keterangan.trim() || "Disetujui oleh petugas"
            });
            Notify.success('Booking berhasil disetujui!');
            HistoryController.load(HistoryController.currentFilter);
        } catch (err) {
            Notify.error(err.message || 'Gagal menyetujui booking');
        }
    },

    reject: async (id) => {
        if (!HistoryController.isPetugas()) return Notify.error('Aksi ini hanya untuk Petugas');

        const keterangan = await Notify.promptText('Tolak Booking', 'Alasan penolakan (WAJIB diisi)', '');
        if (!keterangan || keterangan.trim() === '') {
            Notify.error('Alasan penolakan harus diisi!');
            return;
        }

        const confirmed = await Notify.confirm('Yakin menolak booking ini?', 'User akan menerima notifikasi penolakan.', 'Ya, Tolak');
        if (!confirmed) return;

        Notify.loading('Menolak booking...');
        try {
            await Api.post(`/UpdateStatusBooking/${id}`, {
                status: "ditolak",
                keterangan: keterangan.trim()
            });
            Notify.success('Booking berhasil ditolak!');
            HistoryController.load(HistoryController.currentFilter);
        } catch (err) {
            Notify.error(err.message || 'Gagal menolak booking');
        }
    },

    downloadNotulen: async (id, filename = 'notulen.pdf') => {
        if (!id) return Notify.error('ID file tidak valid');

        Notify.loading('Mengunduh notulen...');
        try {
            const res = await Api.get(`/downloadNotulen/${id}`);

            if (!res || res.status !== 'success' || !res.data) {
                throw new Error(res?.message || 'Respons server tidak valid');
            }

            const fileData = res.data;
            if (!fileData.base64 || !fileData.type) {
                throw new Error('File tidak ditemukan atau data rusak');
            }

            const binaryString = atob(fileData.base64);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }

            const blob = new Blob([bytes], { type: fileData.type });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileData.name || filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            Notify.success('Notulen berhasil diunduh!');
        } catch (err) {
            console.error('Download notulen error:', err);
            Notify.error(err.message || 'Gagal mengunduh notulen');
        } finally {
            Notify.close();
        }
    },

    row: (d) => {
        const colors = {
            pending:    'bg-yellow-100 text-yellow-800',
            disetujui:  'bg-green-100 text-green-800',
            ditolak:    'bg-red-100 text-red-800',
            selesai:    'bg-blue-100 text-blue-800'
        };
        const color = colors[d.status] || 'bg-gray-100 text-gray-700';

        // === HANYA PETUGAS YANG BISA MELAKUKAN AKSI ===
        const aksi = (HistoryController.isPetugas() && d.status === 'pending') ? `
            <div class="flex justify-center gap-3">
                <button onclick="HistoryController.approve(${d.id}, '${(d.nama_user||'').replace(/'/g,"\\'")}')"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition">
                    Setujui
                </button>
                <button onclick="HistoryController.reject(${d.id})"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition">
                    Tolak
                </button>
            </div>
        ` : '<span class="text-gray-400 text-center block">-</span>';

        const notulen = d.notulen?.length > 0 ? d.notulen.map(n => {
            const fileId   = n.id_notulen || n.id || '';
            const fileName = (n.name || 'notulen.pdf').replace(/'/g, "\\'").replace(/"/g, '\\"');
            const displayName = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;

            const icon = fileName.toLowerCase().includes('.pdf') ? 'fa-file-pdf' :
                         fileName.match(/\.(jpe?g|png|gif|webp)$/i) ? 'fa-file-image' : 'fa-file';

            return `<button onclick="HistoryController.downloadNotulen(${fileId}, '${fileName}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 rounded text-xs hover:bg-blue-100 transition mb-1">
                        <i class="fas ${icon}"></i> ${displayName}
                    </button>`;
        }).join('<br>') : '<span class="text-gray-400 italic text-xs">Belum ada notulen</span>';

        return `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-8">
                    <div class="font-semibold">${d.nama_user || '-'}</div>
                    <div class="text-sm text-gray-500"><i class="fas fa-door-open"></i> ${d.ruangan_name || '-'}</div>
                </td>
                <td class="px-6 py-5 font-medium">${d.kegiatan || '-'}</td>
                <td class="px-6 py-5">
                    <div class="font-medium">${d.tanggal_mulai || '-'}</div>
                    <div class="text-sm text-gray-500">${(d.jam_mulai||'?').substr(0,5)} - ${(d.jam_selesai||'?').substr(0,5)}</div>
                </td>
                <td class="px-6 py-5 text-center">
                    <span class="px-4 py-2 rounded-full text-xs font-bold uppercase ${color}">
                        ${d.status || 'unknown'}
                    </span>
                </td>
                <td class="px-6 py-5 text-center">${aksi}</td>
                <td class="px-6 py-5">${notulen}</td>
            </tr>`;
    }
};

HistoryController.init();