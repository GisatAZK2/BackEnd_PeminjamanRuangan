var ProfileController = {
    currentUser: null,

    init: () => {
        ProfileController.loadData();
    },

    loadData: async () => {
        const detailContainer = document.getElementById('profile-details');
        const nameDisplay = document.getElementById('profile-name-display');
        const avatar = document.getElementById('profile-avatar');

        const cookie = document.cookie.split('; ').find(row => row.startsWith('user_info='));
        const userCookie = cookie ? JSON.parse(decodeURIComponent(cookie.split('=')[1])) : {};
        const userId = userCookie.id_user;

        try {
            const res = await Api.get(`/users/detail?id_user=${userId}`);
            const u = res.data;
            ProfileController.currentUser = u;

            if (nameDisplay)
                nameDisplay.innerText = u.nama;

            if (avatar)
                avatar.src = "/public/person.svg"; // fix

            detailContainer.innerHTML = `
                ${ProfileController.item('Username', u.username)}
                ${ProfileController.item('Email', u.email)}
                ${ProfileController.item('No. Telepon', u.nomor_telepon || '-')}
                ${ProfileController.item('Divisi', u.nama_divisi || '<span class="text-gray-400">Tidak ada</span>')}
            `;
        } catch (err) {
            Notify.error(err.message);
        }
    },

    item: (label, value) => `
        <div class="border-b border-gray-100 pb-2">
            <p class="text-xs text-gray-500 uppercase tracking-wider">${label}</p>
            <p class="text-gray-800 font-medium mt-1">${value}</p>
        </div>
    `,

    edit: async () => {
        const u = ProfileController.currentUser;

        let divOptions = '<option value="">Loading...</option>';
        try {
            const resDiv = await Api.get('/divisi');
            divOptions = resDiv.data.map(d => 
                `<option value="${d.id_divisi}" ${d.id_divisi == u.id_divisi ? 'selected' : ''}>${d.nama_divisi}</option>`
            ).join('');
        } catch (e) {}

        const { value: form } = await Swal.fire({
            title: 'Edit Profil',
            html: `
                <input id="p-nama" class="swal2-input" value="${u.nama}" placeholder="Nama">
                <input id="p-email" class="swal2-input" value="${u.email}" placeholder="Email">
                <input id="p-telp" class="swal2-input" value="${u.nomor_telepon||''}" placeholder="No HP">
                <select id="p-div" class="swal2-select">${divOptions}</select>
            `,
            focusConfirm: false,
            preConfirm: () => ({
                id_user: u.id_user,
                username: u.username,
                nama: document.getElementById('p-nama').value,
                email: document.getElementById('p-email').value,
                nomor_telepon: document.getElementById('p-telp').value,
                id_divisi: document.getElementById('p-div').value
            })
        });

        if (form) {
            Notify.loading();
            try {
                await Api.put('/users/update', form);
                Notify.success('Profil berhasil diperbarui');

                const newCookie = { ...u, ...form, nama_divisi: u.nama_divisi };
                document.cookie = `user_info=${encodeURIComponent(JSON.stringify(newCookie))}; path=/; samesite=lax`;

                ProfileController.loadData();
            } catch (err) {
                Notify.error(err.message);
            }
        }
    },

   changePassword: async () => {
    const u = ProfileController.currentUser;

    const { value: form } = await Swal.fire({
        title: 'Ganti Password',
        html: `
            <input id="new-pass" type="password" class="swal2-input" placeholder="Password Baru (Min 8)">
            <input id="cnf-pass" type="password" class="swal2-input" placeholder="Ulangi Password Baru">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        preConfirm: () => {
            const baru = document.getElementById('new-pass').value.trim();
            const cnf = document.getElementById('cnf-pass').value.trim();

            if (baru.length < 8) {
                Swal.showValidationMessage('Password minimal 8 karakter');
                return false;
            }
            if (baru !== cnf) {
                Swal.showValidationMessage('Password baru tidak cocok');
                return false;
            }

            return {
                id_user: u.id_user,
                password: baru   
            };
        }
    });

    if (form) {
        Notify.loading();
        try {
            await Api.put('/users/update', form);
            Notify.success('Password berhasil diganti');
        } catch (err) {
            Notify.error(err.response?.data?.message || err.message || 'Gagal mengubah password');
        }
    }
},

    refreshPage: () => {
        location.reload(); 
    }
};

ProfileController.init();
