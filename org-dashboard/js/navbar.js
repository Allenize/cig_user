// navbar.js — mobile sidebar toggle

(function () {
    const sidebar    = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');

    if (!sidebar) return;

    // Mobile sidebar open
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('mobile-open');
        });
    }

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (
            window.innerWidth < 769 &&
            sidebar.classList.contains('mobile-open') &&
            !sidebar.contains(e.target) &&
            (!menuToggle || !menuToggle.contains(e.target))
        ) {
            sidebar.classList.remove('mobile-open');
        }
    });
})();