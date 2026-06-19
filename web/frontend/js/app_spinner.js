/** * app_spinner.js 
 * Global utility for async operation feedback.
 */
window.app = window.app || {};

app.spinner = {
    // Inject the spinner into the DOM if it doesn't exist
    _create() {
        if (document.getElementById('global-spinner')) return;
        
        const overlay = document.createElement('div');
        overlay.id = 'global-spinner';
        overlay.className = 'spinner-overlay'; // Ensure this matches your CSS
        overlay.innerHTML = `<div class="spinner-loader"></div>`;
        document.body.appendChild(overlay);
    },

    show() {
        this._create();
        document.getElementById('global-spinner').style.display = 'flex';
    },

    hide() {
        const el = document.getElementById('global-spinner');
        if (el) el.style.display = 'none';
    }
};