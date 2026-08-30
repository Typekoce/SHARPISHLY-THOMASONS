const IntegrationWorkflowController = {
    async init() {
        const App = window.App;
        if (!App) {
            console.warn('IntegrationWorkflowController: App namespace not available');
            return;
        }

        const recordsContainer = document.getElementById('integration_workflow-records');
        if (!recordsContainer) return;

        let form = document.getElementById('integration_workflow-form');
        if (!form) {
            form = App.item('form');
            form.id = 'integration_workflow-form';
            form.className = 'mb-4';

            const schema = {
                'title': { id: 'integration_workflow-title', placeholder: 'Workflow Title (e.g. CareLog Ingest)...' },
                'source_system': { id: 'integration_workflow-source', placeholder: 'Source System (e.g. CareSys API)...' },
                'target_system': { id: 'integration_workflow-target', placeholder: 'Target System (e.g. Embrace Database)...' }
            };
            App.eForm(form, schema);

            const submitBtn = App.item('button');
            submitBtn.type = 'submit';
            submitBtn.textContent = 'Register Integration Flow';
            form.appendChild(submitBtn);

            form.onsubmit = (e) => this.create(e);

            recordsContainer.parentNode.insertBefore(form, recordsContainer);
        }

        this.submenu();
        await this.read();
    },

    async create(e) {
        e.preventDefault();
        const App = window.App;
        const titleEl = document.getElementById('integration_workflow-title');
        const sourceEl = document.getElementById('integration_workflow-source');
        const targetEl = document.getElementById('integration_workflow-target');

        const payload = {
            title: titleEl?.value.trim(),
            source_system: sourceEl?.value.trim() || 'Internal-API',
            target_system: targetEl?.value.trim() || 'Embrace-Care-Core',
            status: 'pending'
        };

        if (!payload.title) return;

        App.spinner();
        try {
            const res = await fetch(App.url('integrationworkflow/create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status === 'success') {
                document.getElementById('integration_workflow-form').reset();
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    },

    async read(id = '', params = {}) {
        const App = window.App;
        const recordsDiv = document.getElementById('integration_workflow-records');
        if (!recordsDiv) return;

        App.spinner();
        try {
            const endpoint = id ? `integrationworkflow/read/${id}` : 'integrationworkflow/read';
            const res = await fetch(App.url(endpoint));
            const result = await res.json();

            recordsDiv.textContent = '';

            let data = result.data || [];

            if (params.status) {
                data = data.filter(item => item.status === params.status);
            }

            if (result.status !== 'success' || data.length === 0) {
                const emptyMsg = App.item('p');
                emptyMsg.textContent = 'No integration workflows registered.';
                recordsDiv.appendChild(emptyMsg);
                return;
            }

            const table = App.item('table');
            table.className = 'table';

            const thead = App.item('thead');
            const headerRow = App.item('tr');
            ['Workflow', 'Systems (Source -> Target)', 'Status', 'Actions'].forEach(text => {
                const th = App.item('th');
                th.textContent = text;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = App.item('tbody');
            data.forEach(item => {
                const tr = App.item('tr');

                const titleTd = App.item('td');
                const titleStrong = App.item('strong');
                titleStrong.textContent = item.title;
                titleTd.appendChild(titleStrong);

                const sysTd = App.item('td');
                sysTd.textContent = `${item.source_system} -> ${item.target_system}`;

                const statusTd = App.item('td');
                statusTd.textContent = item.status;

                const actionsTd = App.item('td');

                const processBtn = App.item('button');
                processBtn.textContent = 'Trigger AI Process';
                processBtn.className = 'btn btn-sm btn-info me-1';
                processBtn.onclick = () => this.runAiProcess(item.id);

                const deleteBtn = App.item('button');
                deleteBtn.textContent = 'Delete';
                deleteBtn.className = 'btn btn-sm btn-danger';
                deleteBtn.onclick = () => this.delete(item.id);

                actionsTd.appendChild(processBtn);
                actionsTd.appendChild(deleteBtn);

                tr.appendChild(titleTd);
                tr.appendChild(sysTd);
                tr.appendChild(statusTd);
                tr.appendChild(actionsTd);

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            recordsDiv.appendChild(table);
        } finally {
            App.clearSpinner();
        }
    },

    async runAiProcess(id) {
        const App = window.App;
        App.spinner();
        try {
            const res = await fetch(App.url(`integrationworkflow/executeAiIngest/${id}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await res.json();
            if (result.status === 'success') await this.read();
        } finally {
            App.clearSpinner();
        }
    },

    async delete(id) {
        const App = window.App;
        if (!confirm('Delete workflow record?')) return;
        App.spinner();
        try {
            const res = await fetch(App.url(`integrationworkflow/delete/${id}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await res.json();
            if (result.status === 'success') await this.read();
        } finally {
            App.clearSpinner();
        }
    }
};
