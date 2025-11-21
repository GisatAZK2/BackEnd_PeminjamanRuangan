var NotulenController = {
  bookings: [],       
  selectedBookingId: null,
  currentNotulen: [],  

  async init() {
    await this.loadBookings();
    this.setupEventListeners();
    Notify.close();
  },

  async loadBookings() {
    try {
      // Ambil hanya booking yang status "disetujui" atau "selesai" tapi belum ada tanggal_selesai_rapat (artinya belum di-finish)
      const res = await Api.get('/GetHistory?filter=disetujui');
      const all = res.data || [];

      // Filter hanya yang bisa di-finish (status disetujui & tanggal_selesai_rapat null)
      this.bookings = all.filter(b => 
        b.status === 'disetujui' && 
        (!b.tanggal_selesai_rapat || b.tanggal_selesai_rapat === null)
      );

      this.renderBookingSelect();
    } catch (err) {
      Notify.error('Gagal memuat data booking');
    }
  },

  renderBookingSelect() {
    const select = document.getElementById('booking-select');
    if (this.bookings.length === 0) {
      select.innerHTML = '<option value="">Tidak ada booking yang bisa diselesaikan</option>';
      return;
    }

    select.innerHTML = '<option value="">-- Pilih Booking --</option>' +
      this.bookings.map(b => `
        <option value="${b.id}">
          ${b.ruangan_name} - ${b.kegiatan} (${new Date(b.tanggal_mulai).toLocaleDateString('id-ID')})
        </option>
      `).join('');
  },

  setupEventListeners() {
    document.getElementById('booking-select').addEventListener('change', (e) => {
      const id = e.target.value;
      if (!id) {
        this.selectedBookingId = null;
        this.hideBookingInfo();
        this.clearExistingNotulen();
        this.disableUpload();
        return;
      }

      this.selectedBookingId = parseInt(id);
      const booking = this.bookings.find(b => b.id === this.selectedBookingId);
      this.showBookingInfo(booking);
      this.loadExistingNotulen(booking);
      this.enableUpload();
    });

    document.getElementById('files-input').addEventListener('change', () => {
      this.renderPreview();
      this.toggleUploadButton();
    });
  },

  showBookingInfo(booking) {
    document.getElementById('info-ruangan').textContent = booking.ruangan_name;
    document.getElementById('info-kegiatan').textContent = booking.kegiatan;
    document.getElementById('info-tanggal').textContent = 
      new Date(booking.tanggal_mulai).toLocaleDateString('id-ID') + 
      (booking.tanggal_mulai !== booking.tanggal_selesai ? ' s/d ' + new Date(booking.tanggal_selesai).toLocaleDateString('id-ID') : '');
    document.getElementById('info-jam').textContent = `${booking.jam_mulai.substr(0,5)} - ${booking.jam_selesai.substr(0,5)}`;
    document.getElementById('booking-info').classList.remove('hidden');
  },

  hideBookingInfo() {
    document.getElementById('booking-info').classList.add('hidden');
  },

  renderPreview() {
    const files = document.getElementById('files-input').files;
    const container = document.getElementById('preview-container');
    const countSpan = document.getElementById('file-count');
    countSpan.textContent = files.length ? `${files.length} file` : '0 file';

    container.innerHTML = '';
    for (const file of files) {
      if (file.size > 16 * 1024 * 1024) {
        Notify.error(`File ${file.name} terlalu besar (max 16MB)`);
        continue;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        const isImage = file.type.startsWith('image/');
        const div = document.createElement('div');
        div.className = 'text-center';
        if (isImage) {
          div.innerHTML = `<img src="${e.target.result}" class="preview-img" alt="${file.name}">`;
        } else {
          div.innerHTML = `<i class="fas fa-file-pdf text-5xl text-red-500"></i>`;
        }
        div.innerHTML += `<p class="text-xs mt-1 truncate">${file.name}</p>`;
        container.appendChild(div);
      };
      reader.readAsDataURL(file);
    }
  },


  enableUpload() {
    document.getElementById('upload-btn').disabled = false;
    document.getElementById('upload-btn').classList.remove('opacity-50', 'cursor-not-allowed');
  },

  disableUpload() {
    document.getElementById('upload-btn').disabled = true;
    document.getElementById('upload-btn').classList.add('opacity-50', 'cursor-not-allowed');
  },

  toggleUploadButton() {
    const hasFiles = document.getElementById('files-input').files.length > 0;
    const hasBooking = !!this.selectedBookingId;
    if (hasFiles && hasBooking) {
      this.enableUpload();
    } else {
      this.disableUpload();
    }
  },

  async upload() {
    if (!this.selectedBookingId) return Notify.error('Pilih booking dulu');
    const files = document.getElementById('files-input').files;
    if (files.length === 0) return Notify.error('Pilih minimal 1 file');

    const formData = new FormData();
    for (const file of files) {
      if (file.size > 16 * 1024 * 1024) {
        Notify.error(`File ${file.name} melebihi 16MB`);
        return;
      }
      formData.append('files[]', file);
    }

    Notify.loading('Mengunggah notulen dan menyelesaikan rapat...');

    try {
      await Api.upload(`/RoomFinished/${this.selectedBookingId}`, formData);
      Notify.success('Rapat selesai! Notulen berhasil diunggah');
      setTimeout(() => location.reload(), 2000);
    } catch (err) {
      Notify.error('Gagal mengunggah notulen');
    }
  },

  openImage(url) {
    Swal.fire({
      imageUrl: url,
      imageAlt: 'Notulen',
      showConfirmButton: false,
      width: '90%',
      padding: '1rem',
      background: '#000',
    });
  }
};
 NotulenController.init();