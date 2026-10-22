function openSidebar() {
    document.getElementById('sidebar')?.classList.add('is-open');
    document.getElementById('sidebarOverlay')?.classList.add('is-open');
    document.body.classList.add('menu-open');
    updateMenuButton(true);
}

function closeSidebar() {
    document.getElementById('sidebar')?.classList.remove('is-open');
    document.getElementById('sidebarOverlay')?.classList.remove('is-open');
    document.body.classList.remove('menu-open');
    updateMenuButton(false);
}

function toggleSidebar() {
    const isOpen = document.getElementById('sidebar')?.classList.contains('is-open');
    isOpen ? closeSidebar() : openSidebar();
}

function updateMenuButton(isOpen) {
    const button = document.getElementById('menuButton');
    if (!button) return;
    button.setAttribute('aria-expanded', String(isOpen));
    button.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
}

// Tutup sidebar setelah menu dipilih (di HP)
document.querySelectorAll('#sidebar .mobile-menu > a').forEach(function (link) {
    link.addEventListener('click', closeSidebar);
});

// Escape menutup sidebar
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
});

// Kalau layar dibesarkan ke desktop, reset state
window.addEventListener('resize', function () {
    if (window.innerWidth > 1023) closeSidebar();
});