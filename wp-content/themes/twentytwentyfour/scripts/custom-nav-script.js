document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger');
    const menu = document.getElementById('header-menu');

    if (hamburger && menu) {
        hamburger.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    }

    // Dropdown toggle em mobile
    document.querySelectorAll('.dropdown-toggle').forEach(link => {
        link.addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                e.preventDefault(); // impedir navegação
                this.parentElement.classList.toggle('open'); // alterna dropdown
            }
        });
    });
});
