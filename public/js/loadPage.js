const spaContainer = document.getElementById('spa-content');
const allowedPages = [
    'dashboard', 'profile', 'history', 'booking', 'notulen',
    'admin/kelola_users', 'admin/kelola_rooms', 'admin/kelola_divisi'
];

let currentScriptElement = null;   // script page yang sedang aktif
let currentPage = null;            // simpan nama page saat ini

const App = {
    init() {
        const urlParams = new URLSearchParams(window.location.search);
        let initialPage = urlParams.get('halaman') || 'dashboard';

        // Kalau halaman tidak diizinkan → fallback ke dashboard
        if (!allowedPages.includes(initialPage)) initialPage = 'dashboard';

        this.loadPage(initialPage, false);

        this.setupNavigation();
    },

    async loadPage(page, push = true) {
        if (!allowedPages.includes(page)) page = 'dashboard';
        if (currentPage === page) return; // hindari reload page yang sama

        currentPage = page;

        // Update sidebar aktif
        this.updateSidebar(page);

        try {
            const res = await fetch(`/views/pages/${page}.php`);
            if (!res.ok) throw new Error(`Halaman tidak ditemukan (${res.status})`);

            const html = await res.text();

            // Inject HTML
            spaContainer.innerHTML = html;

            // Hapus script page lama (penting biar tidak memory leak + init() tidak double)
            if (currentScriptElement) {
                currentScriptElement.remove();
                currentScriptElement = null;
            }

            // Cari script khusus halaman ini: <script data-script-page src="...">
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newScriptTag = doc.querySelector('script[data-script-page]');

            if (newScriptTag && newScriptTag.src) {
                await this.loadPageScript(newScriptTag.src);
            }

            // Update URL tanpa reload
            if (push) {
                history.pushState({ page }, '', `?halaman=${page}`);
            }

        } catch (err) {
            spaContainer.innerHTML = `
                <div class="text-center py-16">
                    <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                    <p class="text-xl text-red-600">Gagal memuat halaman: ${page}</p>
                    <button onclick="location.reload()" class="mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Muat Ulang Halaman
                    </button>
                </div>`;
            console.error(err);
        }
    },

    // Load script halaman dengan pasti menunggu sampai selesai & init() dipanggil
    loadPageScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src + '?v=' + Date.now(); // cache bust
            script.async = false;        // penting! agar eksekusi berurutan
            script.defer = true;

            script.onload = () => {
                currentScriptElement = script;
                resolve();
            };

            script.onerror = () => {
                reject(new Error(`Gagal load script: ${src}`));
            };

            document.head.appendChild(script); // lebih aman di head
        });
    },

    updateSidebar(page) {
        document.querySelectorAll('#sidebar a[data-page]').forEach(link => {
            const isActive = link.dataset.page === page;

            link.classList.toggle('bg-[#1F2D3D]', isActive);
            link.classList.toggle('text-white', isActive);
            link.classList.toggle('text-gray-300', !isActive);

            const bar = link.querySelector('span.absolute');
            if (bar) bar.classList.toggle('bg-blue-600', isActive);
        });
    },

    setupNavigation() {
        // Klik link SPA
        document.addEventListener('click', e => {
            const link = e.target.closest('a[data-spa]');
            if (link) {
                e.preventDefault();
                const page = link.dataset.page;
                this.loadPage(page);
            }
        });

        // Tombol Back/Forward browser
        window.addEventListener('popstate', e => {
            const page = e.state?.page || new URLSearchParams(location.search).get('halaman') || 'dashboard';
            this.loadPage(page, false);
        });
    }
};

App.init();