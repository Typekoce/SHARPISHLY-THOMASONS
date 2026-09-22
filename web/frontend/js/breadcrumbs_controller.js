/**
 * BreadcrumbsController
 * Simplified breadcrumb manager targeting `#breadcrumbs`.
 */
const BreadcrumbsController = {
    /**
     * Set and render live breadcrumbs in #breadcrumbs
     * @param {Array<{label: string, page?: string, url?: string}>} crumbs
     */
    set(crumbs = []) {
        console.log('[BreadcrumbsController] set() called with crumbs:', crumbs);

        const target = document.querySelector('#breadcrumbs');
        if (!target) {
            console.warn('[BreadcrumbsController] Target element #breadcrumbs was not found in the DOM.');
            return;
        }

        // Default fallback if empty
        const list = Array.isArray(crumbs) && crumbs.length > 0 
            ? crumbs 
            : [{ label: 'Home', page: 'home' }];

        // Generate clean HTML string
        target.innerHTML = list.map((crumb, index) => {
            const isLast = index === list.length - 1;
            const label = this.escape(crumb.label);
            
            if (isLast) {
                return `<span class="active">${label}</span>`;
            }

            // Supports single-page `data-page` navigation or traditional `href`
            const attr = crumb.page 
                ? `href="#" data-page="${this.escape(crumb.page)}"` 
                : `href="${this.escape(crumb.url || '#')}"`;

            return `<a ${attr}>${label}</a> <span class="separator">/</span> `;
        }).join('');

        console.log('[BreadcrumbsController] Rendered HTML:', target.innerHTML);
    },

    /**
     * Helper to update breadcrumbs by route/page name directly
     * @param {string} page
     * @param {string|null} activeSubItem Optional specific sub-item (e.g., Project Name or Todo Title)
     */
    updateForPage(page = 'home', activeSubItem = null) {
        console.log(`[BreadcrumbsController] updateForPage('${page}', '${activeSubItem}')`);

        const root = { label: 'Home', page: 'home' };

        if (page === 'home' || !page) {
            return this.set([root]);
        }

        const pageLabel = page.charAt(0).toUpperCase() + page.slice(1);
        const crumbs = [root, { label: pageLabel, page: page }];

        if (activeSubItem) {
            crumbs.push({ label: activeSubItem });
        }

        this.set(crumbs);
    },

    /**
     * Basic string escaping to prevent XSS in dynamic items
     */
    escape(str) {
        return String(str).replace(/[&<>"']/g, match => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[match]));
    }
};