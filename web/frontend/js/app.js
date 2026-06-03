/**
 * App: Global Utilities & Template Engine
 */
const App = {
    async loadTemplate(url, data = {}) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Missing: ${url}`);
            let template = await res.text();
            return template.replace(/{{\s*([\w.]+)\s*}}/g, (_, path) => {
                const value = path.split('.').reduce((obj, key) => obj?.[key], data);
                if (typeof value === 'boolean') return value ? '<span class="text-success">ONLINE</span>' : '<span class="text-danger">OFFLINE</span>';
                return value ?? '';
            });
        } catch (e) {
            return `<div class="p-4 text-danger">Template Error: ${e.message}</div>`;
        }
    },
    crm() { Model.currentPage = 'home'; Controller.render(); },
    cyberdeck() { Model.currentPage = 'llm'; Controller.render(); },
    url(path) { 
        const cleanPath = path.startsWith('/') ? path.substring(1) : path;
        return `${window.location.origin}/php/${cleanPath}`; 
    },
    getApp() { return document.getElementById('app'); },
    item(e) { return document.createElement(e); },
    flash(msg) {
        const flash = document.getElementById('flash');
        if (!flash) return;
        const alert = App.item('div');
        alert.style.cssText = 'width:100%;border-radius:16px;margin-bottom:10px;padding-top:10px;padding-bottom:10px;background-color:#ccc;text-align:center';
        alert.innerHTML = msg;
        flash.appendChild(alert);
    }
};
