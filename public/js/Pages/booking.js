var BookingController = {

    rooms: [],
    blockedDates: {},   
    bookings: {},      
    selectedRuanganId: null,
    rangeStart: null,
    rangeEnd: null,
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth() + 1, 

    // === INIT UTAMA ===
    async init() {
        this.rangeStart = this.rangeEnd = null;
        this.blockedDates = {};
        this.bookings = {};

        await this.loadRooms();

        if (this.rooms.length > 0) {
            this.selectedRuanganId = this.rooms[0].id;
            document.getElementById('b-room').value = this.selectedRuanganId;
        }

        await this.fetchAvailability();
        this.renderRoomsSelect();
        this.bindEvents();
        this.updateCalendar();
        Notify.close();
    },

    // === LOAD DAFTAR RUANGAN ===
    async loadRooms() {
        try {
            const r = await Api.get('/ruangan');
            this.rooms = r.data || [];
        } catch {
            Notify.error("Gagal memuat daftar ruangan");
        }
    },

    // === RENDER SELECT RUANGAN + BIND CHANGE ===
    renderRoomsSelect() {
        const select = document.getElementById('b-room');
        select.innerHTML = this.rooms
            .map(r => `<option value="${r.id}">${r.ruangan_name}</option>`)
            .join('');

    },

    // === BIND SEMUA EVENT LISTENER ===
    bindEvents() {
        // Ganti ruangan
        document.getElementById('b-room').addEventListener('change', async (e) => {
            this.selectedRuanganId = e.target.value;
            this.rangeStart = this.rangeEnd = null;
            document.getElementById('b-start-date').value = '';
            document.getElementById('b-end-date').value = '';
            await this.fetchAvailability();
            this.updateCalendar();
        });

        // Tombol prev/next month
        document.getElementById('prev-month-btn')?.addEventListener('click', () => this.prevMonth());
        document.getElementById('next-month-btn')?.addEventListener('click', () => this.nextMonth());

        // Tombol refresh
        document.getElementById('refresh-calendar-btn')?.addEventListener('click', () => this.refreshAll());

        // Tombol submit (jika ada di luar form biasa)
        document.querySelector('button[type="submit"]')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.submitForm();
        });
    },

    // === FETCH KETERSEDIAAN (BOOKING YANG SUDAH DISETUJUI) ===
    async fetchAvailability() {
        if (!this.selectedRuanganId) return;

        try {
            const res = await Api.get(`/roomAvailability?ruangan_id=${this.selectedRuanganId}`);
            const data = (res.data && res.data.data) ? res.data.data : res.data || [];

            this.bookings[this.selectedRuanganId] = data;
            this.blockedDates[this.selectedRuanganId] = {};

            data.forEach(booking => {
                const start = new Date(booking.tanggal_mulai);
                const end   = new Date(booking.tanggal_selesai);

                for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                    const key = d.toISOString().split('T')[0];
                    this.blockedDates[this.selectedRuanganId][key] = true;
                }
            });
        } catch (err) {
            console.error("Error fetch availability:", err);
            Notify.error("Gagal mengambil data ketersediaan ruangan");
        }
    },

    // === UPDATE KALENDER ===
    updateCalendar() {
        document.getElementById("calendar-placeholder").innerHTML = this.generateCalendar(this.currentYear, this.currentMonth);
        document.getElementById("cal-title").innerText = new Date(this.currentYear, this.currentMonth - 1)
            .toLocaleString("id-ID", { month: "long", year: "numeric" });
    },

    // === GENERATE HTML KALENDER ===
    generateCalendar(year, month) {
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const today = new Date(); today.setHours(0,0,0,0);

        const blocked = this.blockedDates[this.selectedRuanganId] || {};

        let html = `
            <div class="swal2-calendar">
                <div class="cal-weekdays">
                    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Ming</div>
                </div>
                <div class="cal-grid">
        `;

        const startOffset = (firstDay + 6) % 7;
        for (let i = 0; i < startOffset; i++) {
            html += `<div class="cal-day empty"></div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const currentDate = new Date(dateStr);

            const isPast    = currentDate < today;
            const isBlocked = !!blocked[dateStr];

            let className = "cal-day";
            let onclick   = "";

            if (isPast || isBlocked) {
                className += " blocked";
            } else {
                className += " available";
                onclick = `onclick="BookingController.pickDate('${dateStr}')"`;
            }

            // Highlight range
            if (this.rangeStart && this.rangeEnd) {
                if (dateStr === this.rangeStart || dateStr === this.rangeEnd) className += " selected";
                else if (currentDate >= new Date(this.rangeStart) && currentDate <= new Date(this.rangeEnd)) {
                    className += " in-range";
                }
            } else if (this.rangeStart && dateStr === this.rangeStart) {
                className += " selected";
            }

            html += `<div class="${className}" ${onclick}>${day}</div>`;
        }

        html += `</div></div>`;
        return html;
    },

    // === PILIH TANGGAL ===
    pickDate(dateStr) {
        const blocked = this.blockedDates[this.selectedRuanganId] || {};

        if (blocked[dateStr]) {
            Notify.error("Tanggal tersebut sudah dibooking!");
            return;
        }

        if (!this.rangeStart || this.rangeEnd) {
            this.rangeStart = dateStr;
            this.rangeEnd = null;
        } else {
            if (new Date(dateStr) < new Date(this.rangeStart)) {
                this.rangeEnd = this.rangeStart;
                this.rangeStart = dateStr;
            } else {
                this.rangeEnd = dateStr;
            }

            if (this.isRangeBlocked(this.rangeStart, this.rangeEnd)) {
                Notify.error("Beberapa tanggal dalam range sudah dibooking!");
                this.rangeStart = this.rangeEnd = null;
            }
        }

        document.getElementById('b-start-date').value = this.rangeStart || '';
        document.getElementById('b-end-date').value   = this.rangeEnd   || '';
        this.updateCalendar();
    },

    isRangeBlocked(start, end) {
        const blocked = this.blockedDates[this.selectedRuanganId] || {};
        let d = new Date(start);
        const e = new Date(end);
        while (d <= e) {
            const key = d.toISOString().split('T')[0];
            if (blocked[key]) return true;
            d.setDate(d.getDate() + 1);
        }
        return false;
    },

    prevMonth() {
        if (--this.currentMonth < 1) { this.currentMonth = 12; this.currentYear--; }
        this.updateCalendar();
    },

    nextMonth() {
        if (++this.currentMonth > 12) { this.currentMonth = 1; this.currentYear++; }
        this.updateCalendar();
    },

    async refreshAll() {
        const btn = document.getElementById('refresh-calendar-btn');
        const icon = btn?.querySelector('i');
        if (icon) icon.classList.add('fa-spin');

        this.rangeStart = this.rangeEnd = null;
        document.getElementById('b-start-date').value = '';
        document.getElementById('b-end-date').value = '';

        await this.fetchAvailability();
        this.updateCalendar();

        setTimeout(() => icon?.classList.remove('fa-spin'), 500);
        Notify.success('Ketersediaan ruangan diperbarui!');
    },

    async submitForm() {
        const kegiatan   = document.getElementById("b-activity").value.trim();
        const startDate  = document.getElementById("b-start-date").value;
        const endDate    = document.getElementById("b-end-date").value;
        const startTime  = document.getElementById("b-start").value;
        const endTime    = document.getElementById("b-end").value;

        if (!kegiatan) return Notify.error("Kegiatan wajib diisi");
        if (!startDate || !endDate) return Notify.error("Tanggal wajib dipilih");
        if (!startTime || !endTime) return Notify.error("Jam wajib diisi");
        if (endTime <= startTime) return Notify.error("Jam selesai harus lebih besar");
        if (this.isRangeBlocked(startDate, endDate)) return Notify.error("Tanggal yang dipilih sudah dibooking");

        Notify.loading("Mengirim pengajuan...");

        try {
            await Api.post("/BookingRoom", {
                ruangan_id: this.selectedRuanganId,
                kegiatan,
                tanggal_mulai: startDate,
                tanggal_selesai: endDate,
                jam_mulai: startTime + ":00",
                jam_selesai: endTime + ":00"
            });

            Notify.success("Pengajuan berhasil dikirim!");
            setTimeout(() => location.reload(), 2000);
        } catch (err) {
            console.error(err);
            Notify.close();
        }
    }
};

BookingController.init();