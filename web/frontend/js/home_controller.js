const HomeController = {
    steps: [
        ['sudoers', 'maxie-setup-sudoers'],
        ['app',     'maxie-deploy-sharpishly'],
        ['ollama',  'maxie-prep-ollama'],
        ['health',  'maxie-health-check']
    ],

    async deploy() {
        const btn = document.getElementById('btn-trigger-deploy');
        if (btn) btn.disabled = true;

        for (const [id, alias] of this.steps) {
            this.setStatus(id, 'running');

            try {
                const res = await fetch(`/php/terminal/load/${encodeURIComponent(alias)}`);

                if (!res.ok) {
                    this.setStatus(id, 'failed');
                    break;
                }
                this.setStatus(id, 'done');
            } catch {
                this.setStatus(id, 'failed');
                break;
            }
        }

        if (btn) btn.disabled = false;
    },

    setStatus(id, status) {
        const bar = document.getElementById(`bar-${id}`);
        const badge = document.getElementById(`badge-${id}`);

        const config = {
            running: { width: '50%',  bar: 'progress-bar progress-bar-striped progress-bar-animated bg-primary', badge: 'badge bg-warning', text: 'Running...' },
            done:    { width: '100%', bar: 'progress-bar bg-success', badge: 'badge bg-success', text: 'Complete' },
            failed:  { width: '100%', bar: 'progress-bar bg-danger',  badge: 'badge bg-danger',  text: 'Failed' }
        }[status];

        if (bar)   { bar.style.width = config.width; bar.className = config.bar; }
        if (badge) { badge.className = config.badge; badge.textContent = config.text; }
    }
};