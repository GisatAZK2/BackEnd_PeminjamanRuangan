const Notify = {
    success: (message) => {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    },
    
    error: (message) => {
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: message,
            confirmButtonColor: '#d33'
        });
    },

    loading: (message = 'Memproses...') => {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    },

    close: () => Swal.close(),

    confirm: async (title, text, confirmText = 'Ya, Lanjutkan') => {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal'
        });
        return result.isConfirmed;
    },

    // Form Input (Contoh untuk edit sederhana)
    promptText: async (title, label, value = '') => {
        const { value: text } = await Swal.fire({
            title: title,
            input: 'text',
            inputLabel: label,
            inputValue: value,
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value) return 'Input tidak boleh kosong!';
            }
        });
        return text;
    }
};