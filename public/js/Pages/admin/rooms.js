var RoomController = {
    roomList: [],

    init: async () => {
        await RoomController.load();
    },

    load: async () => {
        const tbody   = document.getElementById('room-body');
        const loading = document.getElementById('room-loading');
        const empty   = document.getElementById('room-empty');

        tbody.innerHTML = '';
        loading.classList.remove('hidden');
        empty.classList.add('hidden');

        try {
            const res = await Api.get('/ruangan');
            loading.classList.add('hidden');

            // Update cache global
            RoomController.roomList = res.data || [];

            if (RoomController.roomList.length === 0) {
                empty.classList.remove('hidden');
                return;
            }

            RoomController.roomList.forEach(r => {
                const safeName = r.ruangan_name
                    .replace(/'/g, "\\'")
                    .replace(/"/g, '\\"');

                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-900">${r.id}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">${r.ruangan_name}</td>
                        <td class="py-4">
                            <div class="flex justify-center gap-6">
                                <button onclick="RoomController.edit(${r.id}, '${safeName}')"
                                        class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="RoomController.delete(${r.id})"
                                        class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });
        } catch (err) {
            loading.classList.add('hidden');
            Notify.error('Gagal memuat data ruangan');
            console.error(err);
        }
    },

    // === CRUD ACTIONS ===

    add: async () => {
        const nama = await Notify.promptText('Tambah Ruangan', 'Masukkan nama ruangan');
        if (!nama?.trim()) return;

        Notify.loading();
        try {
            await Api.post('/AddRoom', { ruangan_name: nama.trim() });
            Notify.success('Ruangan berhasil ditambah');
            await RoomController.load();  // refresh cache + tabel
        } catch (e) {
            Notify.error('Gagal menambah ruangan');
        } finally {
            Notify.close();
        }
    },

    edit: async (id, oldName) => {
        const nama = await Notify.promptText('Edit Ruangan', 'Nama ruangan', oldName);
        if (!nama || nama.trim() === oldName) return;

        Notify.loading();
        try {
            await Api.put(`/ruangan/${id}`, { ruangan_name: nama.trim() });
            Notify.success('Ruangan berhasil diupdate');
            await RoomController.load();
        } catch (e) {
            Notify.error('Gagal mengupdate ruangan');
        } finally {
            Notify.close();
        }
    },

    delete: async (id) => {
        const confirm = await Notify.confirm(
            'Hapus ruangan ini?',
            'Semua data booking untuk ruangan ini akan ikut terhapus permanen.'
        );
        if (!confirm) return;

        Notify.loading();
        try {
            await Api.delete(`/ruangan/${id}`);
            Notify.success('Ruangan berhasil dihapus');
            await RoomController.load();
        } catch (e) {
            Notify.error('Gagal menghapus ruangan');
        } finally {
            Notify.close();
        }
    }
};

RoomController.init();