/**
 * Mobile Controller
 */
const MobileController = {
    init() {
        if (typeof App !== 'undefined' && App.log) {
            App.log(App);
        }
        this.openScreen('screen-home');
    },

    // View Routing
    openScreen(screenId) {
        const screens = document.querySelectorAll('#mobile .screen');
        screens.forEach(s => s.classList.remove('active'));

        const target = document.getElementById(screenId);
        if (target) {
            target.classList.add('active');
        }
    },

    /**
     * Builds and returns a DOM element card matching #agentic markup template
     */
    cardHTML(title, category, text, ts) {
        // Main container
        const card = document.createElement('div');
        card.className = 'job-card';
        card.setAttribute('data-category', category);

        // Header Row
        const headerRow = document.createElement('div');
        headerRow.className = 'job-header-row';

        const h2 = document.createElement('h2');
        h2.textContent = title;

        const badge = document.createElement('span');
        badge.className = 'badge success';
        badge.textContent = 'Dispatched';

        headerRow.appendChild(h2);
        headerRow.appendChild(badge);

        // Subtitle / Company
        const company = document.createElement('h3');
        company.className = 'company-name';
        company.textContent = 'Sharpishly Agent ';

        const viaSpan = document.createElement('span');
        viaSpan.textContent = 'via Client Local';
        company.appendChild(viaSpan);

        // Summary Body
        const summary = document.createElement('p');
        summary.className = 'job-summary';
        summary.textContent = text;

        // Footer
        const footer = document.createElement('div');
        footer.className = 'job-footer';

        const timestamp = document.createElement('span');
        timestamp.className = 'timestamp';
        timestamp.textContent = `Created: ${ts}`;

        const btn = document.createElement('button');
        btn.className = 'btn btn-primary';
        btn.textContent = 'View Agent';

        footer.appendChild(timestamp);
        footer.appendChild(btn);

        // Assemble Card
        card.appendChild(headerRow);
        card.appendChild(company);
        card.appendChild(summary);
        card.appendChild(footer);

        return card;
    },

    // Convert text message -> new Agent card
    generateAgent() {
        const input = document.getElementById('agent-instruction');
        const text = input ? input.value.trim() : '';

        if (!text) {
            alert('Please enter an instruction message.');
            return;
        }

        // Infer category based on input context
        let title = "Sharpishly Agent";
        let category = "career";
        const lower = text.toLowerCase();

        if (lower.includes('doctor') || lower.includes('health') || lower.includes('chemist') || lower.includes('gp')) {
            title = "Doctors / Health";
            category = "health";
        } else if (lower.includes('tax') || lower.includes('council') || lower.includes('manchester')) {
            title = "Manchester City Council";
            category = "bills";
        } else if (lower.includes('bill') || lower.includes('virgin') || lower.includes('tv') || lower.includes('netflix')) {
            title = "Subscription Service";
            category = "subscription";
        }

        const ts = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        const cardElement = this.cardHTML(title, category, text, ts);

        const agentList = document.getElementById('agentic-list');
        if (agentList) {
            agentList.prepend(cardElement);
        }

        input.value = '';
        this.openScreen('screen-agentic');
    }
};