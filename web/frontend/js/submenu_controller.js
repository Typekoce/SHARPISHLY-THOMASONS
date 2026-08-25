SubMenuController = {};

/**
 * Render dynamic submenu action buttons/links
 * @param {Array<{label: string, action: Function, class?: string}>} items
 */
SubMenuController.menu = function(items = []) {
    const container = App.getItem('submenu');
    App.log(container);
    if (!container) return;

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = '';
        return;
    }

    const nav = document.createElement('nav');
    nav.className = 'submenu-nav';

    items.forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = item.class || 'btn btn-sm btn-secondary me-2';
        btn.textContent = item.label;

        if (typeof item.action === 'function') {
            btn.addEventListener('click', (e) => item.action(e));
        }

        nav.appendChild(btn);
    });

    container.innerHTML = '';
    container.appendChild(nav);
};