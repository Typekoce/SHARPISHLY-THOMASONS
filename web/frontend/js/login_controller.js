const LoginController = {
    async init() {
        this.submenu();
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
                action: () => this.setEmail()
            },
            'google': { 
                id: 'login-google', 
                label: 'Sign in with Google', 
                class: 'btn btn-outline-danger w-100 mb-2',
                action: () => this.setGoogle()
            },
            'github': { 
                id: 'login-github', 
                label: 'Sign in with GitHub', 
                class: 'btn btn-outline-dark w-100 mb-2',
                action: () => this.setGitHub()
            },
            'microsoft': { 
                id: 'login-microsoft', 
                label: 'Sign in with Microsoft', 
                class: 'btn btn-outline-primary w-100 mb-2',
                action: () => this.setMicrosoft()
            },
            'apple': { 
                id: 'login-apple', 
                label: 'Sign in with Apple', 
                class: 'btn btn-outline-secondary w-100 mb-2',
                action: () => this.setApple()
            },
            'passkey': { 
                id: 'login-passkey', 
                label: 'Sign in with Passkey', 
                class: 'btn btn-outline-info w-100 mb-2',
                action: () => this.setPasskey()
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
        App.spinner();
        window.location.href = App.url(`auth/${provider}`);
    },

    async setEmail() {
        App.dBug({ context: 'LoginController.setEmail' });
    },

    async setGoogle() {
        this.handleAuth('google');
    },

    async setGitHub() {
        this.handleAuth('github');
    },

    async setMicrosoft() {
        this.handleAuth('microsoft');
    },

    async setApple() {
        this.handleAuth('apple');
    },

    async setPasskey() {
        App.dBug({ context: 'LoginController.setPasskey' });
    }
};