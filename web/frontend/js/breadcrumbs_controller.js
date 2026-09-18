const BreadcrumbsController = {
    /**
     * Retrieve current breadcrumbs array from state store
     */
    get() {
        const breadcrumbs = App.getItem('breadcrumbs');
        return Array.isArray(breadcrumbs) ? breadcrumbs : [];
    },

    /**
     * Set breadcrumbs array in state store and update UI
     * @param {Array<{label: string, url: string}>} crumbs 
     */
    set(crumbs = []) {
        const validatedCrumbs = Array.isArray(crumbs) && crumbs.length > 0 
            ? crumbs 
            : [{ label: 'Home', url: '/' }];

        App.setItem('breadcrumbs', validatedCrumbs);
        this.render();
    },

    /**
     * Reset state back to root
     */
    reset() {
        this.set([{ label: 'Home', url: '/' }]);
    },

    /**
     * Render breadcrumb elements directly into the DOM using programmatic nodes
     * @param {string} containerSelector 
     */
    render(containerSelector = '#breadcrumbs-container') {
        const container = document.querySelector(containerSelector);
        if (!container) return;

        // Clear existing children
        container.textContent = '';

        const crumbs = this.get();
        if (crumbs.length === 0) return;

        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'breadcrumb');

        const ol = document.createElement('ol');
        ol.className = 'breadcrumb-list';
        ol.style.display = 'flex';
        ol.style.listStyle = 'none';
        ol.style.padding = '0';
        ol.style.margin = '0';

        crumbs.forEach((crumb, index) => {
            const isLast = index === crumbs.length - 1;
            const li = document.createElement('li');
            li.className = `breadcrumb-item${isLast ? ' active' : ''}`;

            if (isLast) {
                const span = document.createElement('span');
                span.textContent = crumb.label;
                li.appendChild(span);
            } else {
                const a = document.createElement('a');
                a.href = crumb.url;
                a.textContent = crumb.label;
                li.appendChild(a);

                const separator = document.createElement('span');
                separator.className = 'separator';
                separator.style.margin = '0 8px';
                separator.textContent = '/';
                li.appendChild(separator);
            }

            ol.appendChild(li);
        });

        nav.appendChild(ol);
        container.appendChild(nav);
    }
};