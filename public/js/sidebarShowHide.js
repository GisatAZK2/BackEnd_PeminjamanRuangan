document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('main-content');
  const toggleIcon = document.getElementById('sidebarToggleIcon');
  const menuLabels = document.querySelectorAll('.menu-label');

  if (!sidebar) {
    console.error('Sidebar tidak ditemukan!');
    return;
  }

  // === STATE ===
  let isCollapsed = false;
  let isMobileOpen = false;

  // === FUNGSI UTAMA ===
  window.toggleSidebar = function () {
    if (window.innerWidth < 1024) return;
    isCollapsed = !isCollapsed;
    updateSidebar();
  };

  window.toggleMobileSidebar = function () {
    isMobileOpen = !isMobileOpen;
    if (isMobileOpen) {
      sidebar.classList.add('mobile-open');
      document.body.style.overflow = 'hidden';
    } else {
      sidebar.classList.remove('mobile-open');
      document.body.style.overflow = '';
    }
    updateIcon();
  };

  function updateSidebar() {
    if (window.innerWidth < 1024) return;

    if (isCollapsed) {
      sidebar.classList.add('sidebar-collapsed');
      mainContent.classList.remove('lg:pl-64');
      mainContent.classList.add('lg:pl-16');
    } else {
      sidebar.classList.remove('sidebar-collapsed');
      mainContent.classList.add('lg:pl-64');
      mainContent.classList.remove('lg:pl-16');
    }
    menuLabels.forEach(l => l.style.display = isCollapsed ? 'none' : 'inline');
    updateIcon();
  }

  function updateIcon() {
    if (toggleIcon) {
      toggleIcon.className = isCollapsed
        ? 'fas fa-expand-arrows-alt text-lg'
        : 'fas fa-compress-arrows-alt text-lg';
    }
  }

  // === INIT STATE ===
  if (window.innerWidth >= 1024) {
    sidebar.classList.remove('mobile-open');
  } else {
    sidebar.classList.remove('mobile-open');
    isMobileOpen = false;
  }
  updateSidebar();

  // === RESIZE ===
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
      sidebar.classList.remove('mobile-open');
      isMobileOpen = false;
      document.body.style.overflow = '';
    }
    updateSidebar();
  });

  // === KLIK LUAR (MOBILE) ===
  document.addEventListener('click', (e) => {
    if (
      window.innerWidth < 1024 &&
      isMobileOpen &&
      !sidebar.contains(e.target) &&
      !e.target.closest('[onclick*="toggleMobileSidebar"]')
    ) {
      toggleMobileSidebar();
    }
  });

  // === DROPDOWN USER ===
  const dropdownBtn = document.getElementById('userDropdownBtn');
  const dropdown = document.getElementById('userDropdown');
  if (dropdownBtn && dropdown) {
    dropdownBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', () => dropdown.classList.add('hidden'));
  }

  // === LOGOUT ===
window.logout = async function () {
    Notify.loading('Sedang logout...');

    try {
        const res = await fetch('/api/logout', {
            method: 'POST',
            credentials: 'include' // penting untuk kirim cookie/session
        });

        const data = await res.json();

        if (data.status === 'success') {
            // Hapus cookie manual (cadangan)
            document.cookie = 'user_info=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT; Secure; SameSite=None';

            // Tampilkan notifikasi sukses
            Notify.success(data.message || 'Logout berhasil! Sampai jumpa kembali 👋');

            // Redirect setelah 1.5 detik biar user bisa baca pesan
            setTimeout(() => {
                window.location.href = '/';
            }, 1500);

        } else {
            throw new Error(data.message || 'Gagal logout');
        }
    } catch (err) {
        console.error('Logout error:', err);
        Notify.error('Gagal logout. Silakan coba lagi.');
    }
};

});