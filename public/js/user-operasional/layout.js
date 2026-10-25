document.addEventListener('DOMContentLoaded', function () {
    const menuButton = document.getElementById('opMenuButton');
    const sidebar = document.getElementById('opSidebar');
    const overlay = document.getElementById('opSidebarOverlay');

    if (!menuButton || !sidebar || !overlay) {
        return;
    }

    const mobileQuery = window.matchMedia('(max-width: 768px)');

    function openSidebar() {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
        document.body.classList.add('op-sidebar-open');

        menuButton.setAttribute('aria-expanded', 'true');
        sidebar.setAttribute('aria-hidden', 'false');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeSidebar() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        document.body.classList.remove('op-sidebar-open');

        menuButton.setAttribute('aria-expanded', 'false');
        sidebar.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('aria-hidden', 'true');
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('is-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    menuButton.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Tutup sidebar setelah menu dipilih pada perangkat mobile.
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (mobileQuery.matches) {
                closeSidebar();
            }
        });
    });

    // Tutup sidebar dengan tombol Escape.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    // Reset status ketika berpindah antara desktop dan mobile.
    mobileQuery.addEventListener('change', closeSidebar);

    // Kondisi awal: sidebar tertutup.
    closeSidebar();
});