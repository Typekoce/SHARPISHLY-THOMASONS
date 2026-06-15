/** app_modal.js */
app.modal = {
    open(content, options = {}) {
        const {
            closeOnBackdrop = true,
            closeOnEscape = true,
            className = ''
        } = options;

        // Create elements
        const overlay = document.createElement('div');
        overlay.id = 'app-modal-overlay';
        overlay.className = 'app-modal-overlay'; // Managed by CSS

        const modal = document.createElement('div');
        modal.className = `modal-content ${className}`; // Managed by CSS
        modal.innerHTML = content;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Event management
        if (closeOnBackdrop) {
            overlay.onclick = (e) => {
                if (e.target === overlay) this.close();
            };
        }

        if (closeOnEscape) {
            const onKey = (e) => { if (e.key === 'Escape') this.close(); };
            document.addEventListener('keydown', onKey);
            overlay._modalKeyHandler = onKey;
        }
    },
    
    close() {
        const overlay = document.getElementById('app-modal-overlay');
        if (!overlay) return;
        
        if (overlay._modalKeyHandler) {
            document.removeEventListener('keydown', overlay._modalKeyHandler);
        }
        overlay.remove();
    }
};