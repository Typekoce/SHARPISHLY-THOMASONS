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
    cardHTML(title, category, text, ts, prompt = null) {
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
            this.showDetail(title, text, ts, prompt);
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
        }
    },

    async getRecords() {
        try {
            const response = await fetch(App.url('mobile-agent'));
            const res = await response.json();

            if (res.records && Array.isArray(res.records)) {
                const agentList = document.getElementById('agentic-list');
                if (!agentList) return;

                agentList.innerHTML = '';
                res.records.forEach(item => {
                    const title = item.agent_name || item.title || 'Sharpishly Agent';
                    const category = item.role || item.category || 'career';
                    const text = item.description || item.message || item.summary || '';
                    const ts = item.created_at || '';
                    const prompt = item.content || null;

                    const card = this.cardHTML(title, category, text, ts, prompt);
                    agentList.appendChild(card);
                });
            }
        } catch (err) {
            console.error('Failed to fetch agent records:', err);
        }
    },

    showDetail(title, text, ts, prompt = null) {
        const titleEl = document.getElementById('detail-title');
        const summaryEl = document.getElementById('detail-summary');
        const tsEl = document.getElementById('detail-timestamp');

        if (titleEl) titleEl.textContent = title;
        if (summaryEl) summaryEl.textContent = text;
        if (tsEl) tsEl.textContent = ts;

        const screenDetail = document.getElementById('screen-agent-detail');
        if (screenDetail) {
            let promptEl = document.getElementById('detail-prompt');
            if (promptEl) {
                promptEl.remove();
            }

            let data = prompt;
            if (typeof prompt === 'string') {
                try { data = JSON.parse(prompt); } catch (e) {}
            }

            const mockPrompt = {
                action: 'dispatch_agent',
                target: title,
                instruction: text || 'Execute standard automated workflow',
                status: 'active_pipeline'
            };

            const payload = (data && typeof data === 'object') ? data : mockPrompt;
            promptEl = this.formatPrompt(payload);
            screenDetail.appendChild(promptEl);
        }

        this.openScreen('screen-agent-detail');
    },

    formatPrompt(data) {
        const container = document.createElement('div');
        container.id = 'detail-prompt';
        container.style.cssText = 'margin: 1rem 0; padding: 0.75rem; background: #f4f5f7; border-radius: 6px; font-size: 0.85rem; border: 1px solid #e5e7eb;';

        const buildNode = (obj) => {
            const wrapper = document.createElement('div');

            for (const [key, val] of Object.entries(obj)) {
                const row = document.createElement('div');
                row.style.cssText = 'margin-bottom: 0.4rem; display: flex; flex-direction: column;';

                const label = document.createElement('strong');
                label.textContent = key.toUpperCase();
                label.style.cssText = 'color: #374151; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.1rem;';

                row.appendChild(label);

                if (val !== null && typeof val === 'object') {
                    const nestedContainer = document.createElement('div');
                    nestedContainer.style.cssText = 'padding-left: 0.5rem; border-left: 2px solid #cbd5e1; margin-top: 0.2rem;';
                    nestedContainer.appendChild(buildNode(val));
                    row.appendChild(nestedContainer);
                } else {
                    const valueEl = document.createElement('span');
                    valueEl.textContent = String(val);
                    valueEl.style.cssText = 'color: #1f2937; background: #ffffff; padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid #d1d5db; font-family: monospace; word-break: break-word;';
                    row.appendChild(valueEl);
                }

                wrapper.appendChild(row);
            }
            return wrapper;
        };

        if (data && typeof data === 'object') {
            container.appendChild(buildNode(data));
        } else {
            const fallback = document.createElement('span');
            fallback.textContent = String(data);
            container.appendChild(fallback);
        }

        return container;
    }
};