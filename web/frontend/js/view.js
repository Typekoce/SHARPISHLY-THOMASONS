/** view.js */
/**
 * View: Global App State
 */
const view = {
    neuralPipeline() {
        const container = document.getElementById('neural-pipeline');
        if (!container) return;

        // Clear existing content
        container.innerHTML = '';

        // Create the wrapper
        const np = App.item('div');
        np.className = 'pipeline-list';

        // Map your Model.queue to UI elements
        Model.queue.forEach(item => {
            const row = App.item('div');
            row.className = 'card mb-2 p-2 small border-0 shadow-sm';
            row.innerHTML = `
                <div class="d-flex justify-content-between">
                    <strong>${item.name}</strong>
                    <span class="text-primary">${item.status}</span>
                </div>
            `;
            np.appendChild(row);
        });

        // Append to the target
        container.appendChild(np);
    }
};


document.addEventListener('DOMContentLoaded', () => Controller.init());
// Add this to your init() or at the bottom of script.js
document.addEventListener('click', (e) => {
    // Handle Navbar Toggler
    if (e.target.matches('.navbar-toggler')) {
        document.querySelector('.nav-menu').classList.toggle('show');
    }
});


/* Lightweight replacement for Bootstrap's Collapse JS */
document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-bs-toggle="collapse"]');
    if (toggle) {
        const targetSelector = toggle.getAttribute('data-bs-target');
        const target = document.querySelector(targetSelector);
        if (target) {
            target.classList.toggle('show');
        }
    }
});
