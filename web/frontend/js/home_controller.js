async home() {
        try {
            const app = document.getElementById('app');
            if (!app) {
                console.error('CRITICAL: Element with ID "app" not found in DOM.');
                return;
            }
            
            // Check if App.loadTemplate is working by hardcoding a test string first
            const html = await App.loadTemplate('/views/home.html');
            
            if (html.includes('Template Error')) {
                console.error('Template loading failed:', html);
            }
            
            app.innerHTML = html;
            console.log('Home view injected into #app');
        } catch (err) {
            console.error('Home method execution error:', err);
        }
    },
