/**
 * Init: Execution Entry Point
 */
document.addEventListener('DOMContentLoaded', () => {
    // Controller is now global and has been loaded by the script tag in the HTML
    Controller.init();
});

/* Lightweight replacement for Bootstrap's Collapse/Navbar JS */
document.addEventListener('click', (e) => {
    // Handle Navbar Toggler
    if (e.target.matches('.navbar-toggler')) {
        document.querySelector('.nav-menu').classList.toggle('show');
    }
    
    // Handle Collapse Toggling
    const toggle = e.target.closest('[data-bs-toggle="collapse"]');
    if (toggle) {
        const targetSelector = toggle.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);
        if (target) {
            target.classList.toggle('show');
        }
    }
});
