const LoginController = {
    async init() {

        this.submenu();
        //await this.read();
        this.buttons();
    },

    buttons(container = null) {
        const target = container || document.getElementById('login-form') || document.getElementById('login-records');
        if (!target) return;

        const schema = {
            'email': { 
                id: 'login-email', 
                label: 'Continue with Email', 
                class: 'btn btn-outline-light w-100 mb-2',
                action: () => this.handleAuth('email')
            },
            'google': { 
                id: 'login-google', 
                label: 'Sign in with Google', 
                class: 'btn btn-outline-danger w-100 mb-2',
                action: () => this.handleAuth('google')
            },
            'github': { 
                id: 'login-github', 
                label: 'Sign in with GitHub', 
                class: 'btn btn-outline-dark w-100 mb-2',
                action: () => this.handleAuth('github')
            }
        };

        if (typeof App.eButtons === 'function') {
            //TODO: Add spinner
            App.eButtons(target, schema);
        } else {
            Object.entries(schema).forEach(([provider, cfg]) => {
                const btn = App.item('button');
                btn.id = cfg.id;
                btn.className = cfg.class;
                btn.textContent = cfg.label;
                btn.type = 'button';
                btn.onclick = cfg.action;
                target.appendChild(btn);
            });
        }
    },

    handleAuth(provider) {
        App.dBug({ context: 'LoginController.handleAuth', provider });
        if (provider === 'google' || provider === 'github') {
            window.location.href = App.url(`auth/${provider}`);
        }
    },

};
