var UserController = {
    divisiList: [],
    init: async () => {
        await UserController.loadDivisi();
        await UserController.loadData();
    },
    loadDivisi: async () => {
        try {
            const res = await Api.get('/divisi');
            if (res.status === 'success') UserController.divisiList = res.data;
        } catch (e) { console.error("Gagal load divisi", e); }
    },
    loadData: async () => {
        const tbody = document.getElementById('user-table-body');
        const loading = document.getElementById('user-loading');
       
        tbody.innerHTML = '';
        loading.classList.remove('hidden');
        try {
            const res = await Api.get('/users');
            loading.classList.add('hidden');
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center p-8 text-gray-400">Data kosong</td></tr>`;
                return;
            }
            res.data.forEach(u => {
                const roleColors = {
                    'administrator': 'bg-purple-100 text-purple-700',
                    'petugas': 'bg-yellow-100 text-yellow-700',
                    'peminjam': 'bg-blue-100 text-blue-700'
                };
                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold text-sm">
                                   <img src="/public/person.svg" 
                                   class="mx-auto w-8 h-8 rounded-full mb-3"
                                   alt="User Profile" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">${u.nama}</p>
                                    <p class="text-xs text-gray-500">${u.email}</p>
                                    <p class="text-xs text-gray-400 mt-1">${u.nomor_telepon || '-'}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-xs font-medium ${roleColors[u.role] || 'bg-gray-100'}">
                                ${u.role}
                            </span>
                        </td>
                        <td class="p-4 text-sm">${u.nama_divisi || '<span class="text-gray-400 italic">-</span>'}</td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-6">
                                <button onclick="UserController.edit(${u.id_user})" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition" title="Edit">
                                   <i class="fas fa-edit"></i> Edit
                                </button>
                                <button onclick="UserController.delete(${u.id_user})" class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition" title="Hapus">
                                    <i class="fas fa-trash"> </i> Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } catch (err) {
            loading.innerHTML = `<span class="text-red-500">Error: ${err.message}</span>`;
        }
    },

    // --- ACTIONS ---
    add: async () => {
        const divOptions = UserController.divisiList.map(d => `<option value="${d.id_divisi}">${d.nama_divisi}</option>`).join('');
       
        const { value: form } = await Swal.fire({
            title: 'Tambah User',
            html: `
                <input id="swal-nama" class="swal2-input" placeholder="Nama Lengkap">
                <input id="swal-user" class="swal2-input" placeholder="Username">
                <input id="swal-email" class="swal2-input" type="email" placeholder="Email">
                <input id="swal-phone" class="swal2-input" placeholder="Nomor Telepon (contoh: 08123456789)">
                <input id="swal-pass" class="swal2-input" type="password" placeholder="Password">
                <select id="swal-role" class="swal2-select">
                    <option value="peminjam">Peminjam</option>
                    <option value="petugas">Petugas</option>
                    <option value="administrator">Administrator</option>
                </select>
                <select id="swal-div" class="swal2-select">
                    ${divOptions}
                </select>
            `,
            focusConfirm: false,
            preConfirm: () => {
                return {
                    nama: document.getElementById('swal-nama').value,
                    username: document.getElementById('swal-user').value,
                    email: document.getElementById('swal-email').value,
                    nomor_telepon: document.getElementById('swal-phone').value,
                    password: document.getElementById('swal-pass').value,
                    role: document.getElementById('swal-role').value,
                    id_divisi: document.getElementById('swal-div').value || null,
                }
            }
        });
        if (form) {
            if(!form.nama || !form.username || !form.password || !form.nomor_telepon) 
                return Notify.error('Nama, username, password & nomor telepon wajib diisi');
           
            Notify.loading();
            try {
                await Api.post('/users/add', form);
                Notify.success('User berhasil ditambahkan');
                UserController.loadData();
            } catch (err) {
                Notify.error(err.message || 'Gagal menambah user');
            }
        }
    },

    edit: async (id) => {
        Notify.loading('Mengambil data...');
        try {
            const res = await Api.get(`/users/detail?id_user=${id}`);
            Notify.close();
            const u = res.data;

            const divOptions = UserController.divisiList.map(d =>
                `<option value="${d.id_divisi}" ${d.id_divisi == u.id_divisi ? 'selected' : ''}>${d.nama_divisi}</option>`
            ).join('');

            const { value: form } = await Swal.fire({
                title: 'Edit User',
                html: `
                    <input id="swal-nama" class="swal2-input" value="${u.nama}" placeholder="Nama">
                    <input id="swal-email" class="swal2-input" value="${u.email}" type="email" placeholder="Email">
                    <input id="swal-phone" class="swal2-input" value="${u.nomor_telepon || ''}" placeholder="Nomor Telepon">
                    <input id="swal-pass" class="swal2-input" type="password" placeholder="Password baru (kosongkan jika tidak diganti)">
                    <select id="swal-div" class="swal2-select">
                        ${divOptions}
                    </select>
                `,
                focusConfirm: false,
                preConfirm: () => ({
                    id_user: id,
                    nama: document.getElementById('swal-nama').value,
                    email: document.getElementById('swal-email').value,
                    nomor_telepon: document.getElementById('swal-phone').value,
                    password: document.getElementById('swal-pass').value || undefined,
                    id_divisi: document.getElementById('swal-div').value || null
                })
            });

            if (form) {
                Notify.loading();
                await Api.put('/users/update', form);
                Notify.success('Data user berhasil diperbarui');
                UserController.loadData();
            }
        } catch(err) {
            Notify.close();
            Notify.error(err.message || 'Gagal mengedit user');
        }
    },

    delete: async (id) => {
        const yakin = await Notify.confirm('Hapus User?', 'Data user ini tidak bisa dikembalikan.');
        if (yakin) {
            Notify.loading();
            try {
                await Api.delete('/users/delete', { id_user: id });
                Notify.success('User dihapus');
                UserController.loadData();
            } catch (err) {
                Notify.error(err.message);
            }
        }
    }
};

UserController.init();