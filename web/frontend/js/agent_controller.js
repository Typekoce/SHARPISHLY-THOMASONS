Object.assign(Controller, {
    async bindAgents() {
        const fields = { 'create': {}, 'read': {}};
        const menu = document.getElementById('menu');
        if (menu) this.menuFields(fields, menu);
    },

    async displayAgentRecords(form) {
        form.innerHTML = '<h3>Active Agents</h3>';
        try {
            const res = await fetch(App.url('agent/index'));
            const agents = await res.json();
            const table = App.item('table');
            table.className = 'agent-table';
            table.innerHTML = `<thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>`;
            const tbody = App.item('tbody');
            
            agents.forEach(agent => {
                const tr = App.item('tr');
                const actions = [{id: 'start', name: 'Start'}, {id: 'stop', name: 'Stop'}, {id: 'email', name: 'Write Email'}, {id: 'edit', name: 'Edit'}, {id: 'delete', name: 'Delete'}];
                tr.innerHTML = `<td>${agent.agent_name}</td><td>${agent.role}</td><td><span class="badge ${agent.status}">${agent.status}</span></td>`;
                const actionTd = document.createElement('td');
                const select = App.selectList(actions, (actionId) => {
                    if (actionId === 'email') this.prepareEmailFormForAgent(agent);
                });
                actionTd.appendChild(select);
                tr.appendChild(actionTd);
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            form.appendChild(table);
        } catch (e) { form.innerHTML += '<p>No agents found or error loading.</p>'; }
    },

    async createAgent(form) {
        form.innerHTML = '';
        const fields = { 'agent_name': {}, 'description': {}, 'role': {} };
        this.createAgentForm(form, fields);

        const btn = App.item('div');
        btn.className = 'btn btn-outline-primary mt-2';
        btn.innerHTML = "Save Agent";
        
        btn.onclick = async () => {
            const postData = this.eFields(fields);
            try {
                const res = await fetch(App.url('agent/test/'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(postData)
                });
                const data = await res.json();
                App.flash('Success id:' + data.id + ' created');
            } catch (e) { App.flash('Error creating agent'); }
        };
        form.appendChild(btn);
    },

    createAgentForm(form, fields) {
        for (let field in fields) {
            const input = App.item('input');
            input.setAttribute('id', field);
            input.className = 'form-control';
            const label = App.item('label');
            label.innerHTML = field.replace('_', ' ');
            const row = App.item('div');
            row.className = 'mb-2';
            row.appendChild(label);
            row.appendChild(input);
            form.appendChild(row);
        }
    },

    async menuFields(fields, menu) {
        for (let field in fields) {
            let item = App.item('div');
            item.innerHTML = field;
            item.id = field;
            item.className = 'menu-item';
            item.onclick = async () => {
                let form = document.getElementById('form');
                if (form) this.handleMenuClick(field, form);
            };
            menu.appendChild(item);
        }
    },

    handleMenuClick(field, form) {
        switch(field) {
            case 'create': this.createAgent(form); break;
            case 'read': this.displayAgentRecords(form); break;
            case 'delete':
                let msg = App.item('div');
                msg.innerHTML = "Create a new agent to do the tasks you avoid";
                form.appendChild(msg);
                break;
        }
    }
});
