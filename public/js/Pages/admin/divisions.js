var DivisiController = {
    divisiList: [],

    init: async () => {
        await DivisiController.load(); 
    },

    load: async () => {
        const tbody   = document.getElementById('divisi-body');
        const loading = document.getElementById('divisi-loading');
        const empty   = document.getElementById('divisi-empty');

        tbody.innerHTML = '';
        loading.classList.remove('hidden');
        empty.classList.add('hidden');

        try {
            const res = await Api.get('/divisi');
            loading.classList.add('hidden');

            DivisiController.divisiList = res.data || [];

            if (DivisiController.divisiList.length === 0) {
                empty.classList.remove('hidden');
                return;
            }

            DivisiController.divisiList.forEach(d => {
                const safeName = d.nama_divisi
                    .replace(/'/g, "\\'")
                    .replace(/"/g, '\\"');

                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-900">${d.id_divisi}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">${d.nama_divisi}</td>
                        <td class="flex justify-center gap-6 py-4">
                            <button onclick="DivisiController.edit(${d.id_divisi}, '${safeName}')"
                                    class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                                    title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="DivisiController.delete(${d.id_divisi})"
                                    class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition"
                                    title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
        } catch (err) {
            loading.classList.add('hidden');
            Notify.error('Gagal memuat data divisi');
            console.error(err);
        }
    },

    // === CRUD ACTIONS (setelah sukses selalu refresh cache & tabel) ===

    add: async () => {
        const nama = await Notify.promptText('Tambah Divisi', 'Masukkan nama divisi');
        if (!nama?.trim()) return;

        Notify.loading();
        try {
            await Api.post('/divisi', { nama_divisi: nama.trim() });
            Notify.success('Divisi berhasil ditambah');
            await DivisiController.load();   // refresh cache + tabel
        } catch {
            Notify.error('Gagal menambah divisi');
        } finally {
            Notify.close();
        }
    },

    edit: async (id, oldName) => {
        const nama = await Notify.promptText('Edit Divisi', 'Nama divisi', oldName);
        if (!nama || nama.trim() === oldName) return;

        Notify.loading();
        try {
            await Api.put(`/divisi/${id}`, { nama_divisi: nama.trim() });
            Notify.success('Divisi berhasil diupdate');
            await DivisiController.load();   // refresh cache + tabel
        } catch {
            Notify.error('Gagal mengupdate divisi');
        } finally {
            Notify.close();
        }
    },

    delete: async (id) => {
        const confirm = await Notify.confirm(
            'Hapus divisi ini?',
            'Semua user di divisi ini akan menjadi "Tanpa Divisi".'
        );
        if (!confirm) return;

        Notify.loading();
        try {
            await Api.delete(`/divisi/${id}`);
            Notify.success('Divisi berhasil dihapus');
            await DivisiController.load();  
        } catch {
            Notify.error('Gagal menghapus divisi');
        } finally {
            Notify.close();
        }
    }
};

DivisiController.init();