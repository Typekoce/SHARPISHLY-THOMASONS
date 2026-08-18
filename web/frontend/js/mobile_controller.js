/**
 * Mobile Controller
 */
const MobileController = {
    init() {
        if (typeof App !== 'undefined' && App.log) {
            App.log(App);
        }
        this.getRecords();
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
    cardHTML(title, category, text, ts, output = '') {
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
        btn.onclick = (e) => {
            e.stopPropagation();
            this.showDetail(title, text, ts, output);
        };

        footer.appendChild(timestamp);
        footer.appendChild(btn);

        // Assemble Card
        card.appendChild(headerRow);
        card.appendChild(company);
        card.appendChild(summary);
        card.appendChild(footer);

        return card;
    },

    async generateAgent() {
        const input = document.getElementById('agent-instruction');
        const instruction = input?.value.trim();

        if (!instruction) {
            return alert('Please enter an instruction message.');
        }

        try {
            const response = await fetch(App.url('mobile-agent-create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ instruction })
            });

            const res = await response.json();

            if (res.status !== 'success' && !res.data) {
                return alert(res.error || 'Failed to dispatch agent plan.');
            }

            input.value = '';
            await this.getRecords();
            this.openScreen('screen-agentic');
        } catch (err) {
            console.error('Error dispatching agent:', err);
            alert('Failed to dispatch agent plan.');
        }
    },

    async getRecords() {
        try {
            const response = await fetch(App.url('mobile-agent'));
            const res = await response.json();

            if (!res.records || !Array.isArray(res.records)) {
                return;
            }

            const agentList = document.getElementById('agentic-list');
            if (!agentList) return;

            agentList.innerHTML = '';
            res.records.forEach(item => {
                const title = item.agent_name || item.title || 'Sharpishly Agent';
                const category = item.role || item.category || 'career';
                const text = item.description || item.message || item.summary || '';
                const ts = item.created_at || '';
                const output = item.output ?? item.result ?? item.parsed ?? item.instruction ?? item.prompt ?? '';

                const card = this.cardHTML(title, category, text, ts, output);
                agentList.appendChild(card);
            });
        } catch (err) {
            console.error('Failed to fetch agent records:', err);
        }
    },

    showDetail(title, text, ts, output = '') {
        const titleEl = document.getElementById('detail-title');
        const summaryEl = document.getElementById('detail-summary');
        const tsEl = document.getElementById('detail-timestamp');
        const outputEl = document.getElementById('detail-output');

        if (titleEl) titleEl.textContent = title;
        if (summaryEl) summaryEl.textContent = text;
        if (tsEl) tsEl.textContent = ts;

        if (outputEl) {
            const fallbackPrompt = {
                action: 'dispatch_agent',
                target: title,
                instruction: text || 'Execute standard automated workflow',
                status: 'active_pipeline'
            };

            const activeOutput = output || fallbackPrompt;

            outputEl.textContent = typeof activeOutput === 'object'
                ? JSON.stringify(activeOutput, null, 2)
                : String(activeOutput);

            outputEl.style.display = 'block';
        }

        this.openScreen('screen-agent-detail');
    }
};