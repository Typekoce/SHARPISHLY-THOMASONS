/**
 * Mobile Controller
 */
const MobileController = {
    init() {
        this.log('Initializing MobileController', 'MobileController:init', 'info');
        this.getRecords();
        this.openScreen('screen-home');
        this.bindEvents();
    },

    log(data, label = 'MobileController', level = 'log') {
        if (typeof App !== 'undefined' && App.debug) {
            App.debug(data, label, level);
        }
    },

    getUrl(endpoint) {
        return (typeof App !== 'undefined' && App.url) 
            ? App.url(endpoint) 
            : `/php/${endpoint}`;
    },

    bindEvents() {
        const agentList = document.getElementById('agentic-list');
        if (!agentList) {
            this.log('agentic-list container not found', 'MobileController:bindEvents', 'warn');
            return;
        }

        agentList.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-view-agent');
            if (!btn) return;

            const card = btn.closest('.job-card');
            if (!card) return;

            this.log('Agent view button clicked', 'MobileController:bindEvents', 'info');

            const title = card.dataset.title || '';
            const text = card.dataset.text || '';
            const ts = card.dataset.ts || '';
            let output = card.dataset.output || '';

            try {
                output = JSON.parse(output);
            } catch (_) {
                this.log('Output data is raw string, not JSON', 'MobileController:bindEvents', 'debug');
            }

            this.showDetail(title, text, ts, output);
        });
    },

    openScreen(screenId) {
        this.log(`Opening screen: #${screenId}`, 'MobileController:openScreen', 'info');
        document.querySelectorAll('#mobile .screen').forEach(s => s.classList.remove('active'));
        const target = document.getElementById(screenId);
        if (target) {
            target.classList.add('active');
        } else {
            this.log(`Target screen #${screenId} not found`, 'MobileController:openScreen', 'warn');
        }
    },

    showDetailLayout() {
        this.log('Step 1: Checking for existing #screen-detail', 'MobileController:showDetailLayout', 'debug');
        let screen = document.getElementById('screen-detail');
        if (screen) {
            this.log('Step 1a: Existing #screen-detail found in DOM, reusing instance', 'MobileController:showDetailLayout', 'info');
            return screen;
        }

        this.log('Step 2: Creating outer #screen-detail container', 'MobileController:showDetailLayout', 'debug');
        screen = document.createElement('div');
        screen.id = 'screen-detail';
        screen.className = 'screen';
        screen.setAttribute('role', 'region');
        screen.setAttribute('aria-label', 'Agent Detail Screen');

        this.log('Step 3: Building top navigation bar', 'MobileController:showDetailLayout', 'debug');
        const topbar = document.createElement('div');
        topbar.className = 'detail-topbar';

        const backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'btn btn-secondary';
        backBtn.setAttribute('aria-label', 'Go back to agent list');
        backBtn.textContent = '← Back';
        backBtn.onclick = () => this.openScreen('screen-agentic');

        const badge = document.createElement('span');
        badge.className = 'badge success';
        badge.textContent = 'Active';

        topbar.append(backBtn, badge);

        this.log('Step 4: Constructing detail card layout structure', 'MobileController:showDetailLayout', 'debug');
        const card = document.createElement('div');
        card.className = 'detail-card';

        const h2 = document.createElement('h2');
        h2.className = 'detail-title';

        const p = document.createElement('p');
        p.className = 'detail-summary';

        const outputBox = document.createElement('div');
        outputBox.className = 'detail-output-container';

        const small = document.createElement('small');
        small.className = 'detail-timestamp';

        card.append(h2, p, outputBox, small);
        screen.append(topbar, card);

        this.log('Step 5: Locating DOM placement target within #mobile frame', 'MobileController:showDetailLayout', 'debug');
        const phoneScreen = document.querySelector('#mobile .phone-screen');
        const navBar = phoneScreen?.querySelector('.nav-bar');

        if (navBar) {
            this.log('Step 5a: Inserting #screen-detail before .nav-bar', 'MobileController:showDetailLayout', 'info');
            phoneScreen.insertBefore(screen, navBar);
        } else if (phoneScreen) {
            this.log('Step 5b: Appending #screen-detail to .phone-screen', 'MobileController:showDetailLayout', 'info');
            phoneScreen.appendChild(screen);
        } else {
            this.log('Step 5c: Fallback appending #screen-detail to #mobile root', 'MobileController:showDetailLayout', 'warn');
            const mobileRoot = document.getElementById('mobile');
            if (mobileRoot) mobileRoot.appendChild(screen);
        }

        this.log('Step 6: Detail screen layout injection complete', 'MobileController:showDetailLayout', 'info');
        return screen;
    },

    createCardElement(title, category, text, ts, rawOutput) {
        const card = document.createElement('div');
        card.className = 'job-card';
        card.dataset.category = category;
        card.dataset.title = title;
        card.dataset.text = text;
        card.dataset.ts = ts;
        card.dataset.output = (typeof rawOutput === 'object' && rawOutput !== null) 
            ? JSON.stringify(rawOutput) 
            : String(rawOutput ?? '');

        const headerRow = document.createElement('div');
        headerRow.className = 'job-header-row';

        const h2 = document.createElement('h2');
        h2.textContent = title;

        const badge = document.createElement('span');
        badge.className = 'badge success';
        badge.textContent = 'Dispatched';

        headerRow.append(h2, badge);

        const company = document.createElement('h3');
        company.className = 'company-name';
        company.textContent = 'Sharpishly Agent ';

        const viaSpan = document.createElement('span');
        viaSpan.textContent = 'via Client Local';
        company.appendChild(viaSpan);

        const summary = document.createElement('p');
        summary.className = 'job-summary';
        summary.textContent = text;

        const footer = document.createElement('div');
        footer.className = 'job-footer';

        const timestamp = document.createElement('span');
        timestamp.className = 'timestamp';
        timestamp.textContent = `Created: ${ts}`;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-primary btn-view-agent';
        btn.setAttribute('aria-label', `View details for ${title}`);
        btn.textContent = 'View Agent';

        footer.append(timestamp, btn);
        card.append(headerRow, company, summary, footer);
        
        return card;
    },

    async generateAgent() {
        const input = document.getElementById('agent-instruction');
        const instruction = input?.value.trim();

        if (!instruction) {
            this.log('Empty instruction submitted', 'MobileController:generateAgent', 'warn');
            return alert('Please enter an instruction message.');
        }

        try {
            const url = this.getUrl('mobile-agent-create');
            const response = await fetch(url, {
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
            this.log(err, 'MobileController:generateAgentException', 'error');
            alert('Failed to dispatch agent plan.');
        }
    },

    async getRecords() {
        try {
            const url = this.getUrl('mobile-agent');
            const response = await fetch(url);
            const res = await response.json();

            if (!res.records || !Array.isArray(res.records)) return;

            renderMobileAgents(res);
        } catch (err) {
            this.log(err, 'MobileController:fetchException', 'error');
        }
    },

    /**
     * Converts a variable recursively into dBug-style tabular DOM trees with inline CSS
     */
    buildDBugTable(data, typeName = null) {
        if (data === null || data === undefined) {
            const cell = document.createElement('span');
            cell.style.fontWeight = 'bold';
            cell.style.fontStyle = 'italic';
            cell.textContent = 'NULL';
            return cell;
        }

        if (typeof data === 'boolean') {
            const cell = document.createElement('span');
            cell.style.fontWeight = 'bold';
            cell.style.fontStyle = 'italic';
            cell.textContent = data ? 'TRUE' : 'FALSE';
            return cell;
        }

        if (typeof data !== 'object') {
            const cell = document.createElement('span');
            cell.style.fontFamily = 'Verdana, Arial, Helvetica, sans-serif';
            cell.style.fontSize = '12px';
            cell.textContent = String(data).trim() === '' ? '[empty string]' : String(data);
            return cell;
        }

        const isArr = Array.isArray(data);
        const type = typeName || (isArr ? 'array' : 'object');

        // Color palettes mapped directly from dBug.php
        const isArrayType = type === 'array';
        const theme = {
            tableBg: isArrayType ? '#006600' : '#0000CC',
            borderColor: isArrayType ? 'green' : 'blue',
            headerBg: isArrayType ? '#009900' : '#4444CC',
            keyBg: isArrayType ? '#CCFFCC' : '#CCDDFF'
        };

        const table = document.createElement('table');
        table.className = `dBug_${type}`;
        table.style.fontFamily = 'Verdana, Arial, Helvetica, sans-serif';
        table.style.color = '#000000';
        table.style.fontSize = '12px';
        table.style.backgroundColor = theme.tableBg;
        table.style.border = `1px solid ${theme.borderColor}`;
        table.style.borderCollapse = 'separate';
        table.style.borderSpacing = '2px';
        table.style.margin = '4px auto';
        table.style.width = '90%';

        // Header Row
        const headerTr = document.createElement('tr');
        const headerTd = document.createElement('td');
        headerTd.className = `dBug_${type}Header`;
        headerTd.setAttribute('colspan', '2');
        headerTd.textContent = type;
        headerTd.style.backgroundColor = theme.headerBg;
        headerTd.style.border = `1px solid ${theme.borderColor}`;
        headerTd.style.color = '#FFFFFF';
        headerTd.style.fontWeight = 'bold';
        headerTd.style.cursor = 'pointer';
        headerTd.style.padding = '3px';

        headerTd.addEventListener('click', () => {
            const rows = table.querySelectorAll(':scope > tr:not(:first-child)');
            const isHidden = Array.from(rows).some(r => r.style.display === 'none');
            rows.forEach(r => r.style.display = isHidden ? '' : 'none');
            headerTd.style.fontStyle = isHidden ? 'normal' : 'italic';
        });

        headerTr.appendChild(headerTd);
        table.appendChild(headerTr);

        // Body Rows
        const entries = Object.entries(data);
        if (entries.length === 0) {
            const emptyTr = document.createElement('tr');
            const emptyTd = document.createElement('td');
            emptyTd.setAttribute('colspan', '2');
            emptyTd.style.backgroundColor = '#FFFFFF';
            emptyTd.style.border = `1px solid ${theme.borderColor}`;
            emptyTd.style.padding = '3px';
            emptyTd.textContent = '[empty]';
            emptyTr.appendChild(emptyTd);
            table.appendChild(emptyTr);
            return table;
        }

        entries.forEach(([key, val]) => {
            const tr = document.createElement('tr');

            const keyTd = document.createElement('td');
            keyTd.className = `dBug_${type}Key`;
            keyTd.textContent = key;
            keyTd.style.backgroundColor = theme.keyBg;
            keyTd.style.border = `1px solid ${theme.borderColor}`;
            keyTd.style.cursor = 'pointer';
            keyTd.style.padding = '3px';
            keyTd.style.verticalAlign = 'top';

            keyTd.addEventListener('click', () => {
                const valTd = tr.children[1];
                if (valTd) {
                    valTd.style.display = valTd.style.display === 'none' ? '' : 'none';
                }
            });

            const valTd = document.createElement('td');
            valTd.style.backgroundColor = '#FFFFFF';
            valTd.style.border = `1px solid ${theme.borderColor}`;
            valTd.style.padding = '3px';
            valTd.appendChild(this.buildDBugTable(val));

            tr.append(keyTd, valTd);
            table.appendChild(tr);
        });

        return table;
    },

    renderFormattedOutput(container, data) {
        container.textContent = '';
        this.log('Converting raw output into styled dBug tables', 'MobileController:renderOutput', 'debug');
        
        const dBugNode = this.buildDBugTable(data);
        container.appendChild(dBugNode);
    },

    showDetail(title, text, ts, output = '') {
        this.log('Populating data into detail screen', 'MobileController:showDetail', 'info');
        const screen = this.showDetailLayout();

        const titleEl = screen.querySelector('.detail-title');
        const summaryEl = screen.querySelector('.detail-summary');
        const tsEl = screen.querySelector('.detail-timestamp');
        const outputContainer = screen.querySelector('.detail-output-container');

        if (titleEl) {
            titleEl.textContent = title;
            this.log(`Title set: "${title}"`, 'MobileController:showDetail', 'debug');
        }
        if (summaryEl) {
            summaryEl.textContent = text;
            this.log('Summary populated', 'MobileController:showDetail', 'debug');
        }
        if (tsEl) {
            tsEl.textContent = ts;
            this.log(`Timestamp set: "${ts}"`, 'MobileController:showDetail', 'debug');
        }

        if (outputContainer) {
            this.log('Rendering dBug table layout into container', 'MobileController:showDetail', 'debug');
            this.renderFormattedOutput(outputContainer, output);
        }

        this.openScreen('screen-detail');
    }
};

/**
 * Renders a JSON payload of agent records into the mobile #agentic-list UI.
 */
function renderMobileAgents(payload) {
    const agentList = document.getElementById('agentic-list');
    if (!agentList) return;

    if (!payload?.records || !Array.isArray(payload.records)) {
        agentList.textContent = '';
        return;
    }

    agentList.textContent = '';

    payload.records.forEach(item => {
        renderMobileAgentItem(item);
    });
}

/**
 * Renders a single agent object into a temporary card and appends to #agentic-list.
 */
function renderMobileAgentItem(item) {
    const agentList = document.getElementById('agentic-list');
    if (!agentList) return;

    const title = item.agent_name || item.title || 'Sharpishly Agent';
    const category = item.role || item.category || 'career';
    const text = item.description || item.message || item.summary || '';
    const ts = item.created_at || '';
    const rawOutput =
        item.output ??
        item.result ??
        item.parsed ??
        item.instruction ??
        item.prompt ??
        '';

    const card = MobileController.createCardElement(title, category, text, ts, rawOutput);
    agentList.appendChild(card);
}