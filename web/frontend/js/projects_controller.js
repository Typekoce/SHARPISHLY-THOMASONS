const ProjectsController = {
    async init() {
        const recordsContainer = document.getElementById('projects-records');
        if (!recordsContainer) return;

        let form = document.getElementById('projects-form');
        if (!form) {
            form = App.item('form');
            form.id = 'projects-form';
            form.className = 'mb-4';

            App.eForm(form, {
                'title': { id: 'projects-title', placeholder: 'Title...' },
                'category': { id: 'projects-category', placeholder: 'Category...' },
                'description': { id: 'projects-desc', type: 'textarea', placeholder: 'Description...' }
            });

            // Fetch and inject Todo Dropdown
            const todoSelect = await this.getTodos();
            if (todoSelect) {
                const row = App.item('div');
                row.className = 'mb-3';
                
                const label = App.item('label');
                label.className = 'form-label fw-bold';
                label.textContent = 'Associated Todo';
                
                row.appendChild(label);
                row.appendChild(todoSelect);
                form.appendChild(row);
            }

            const submitBtn = App.item('button');
            submitBtn.type = 'submit';
            submitBtn.className = 'btn btn-primary';
            submitBtn.textContent = 'Add Entry';
            form.appendChild(submitBtn);

            form.onsubmit = (e) => this.create(e);
            recordsContainer.parentNode.insertBefore(form, recordsContainer);
        }

        this.submenu();
        await this.read();
    },

    async getTodos() {
        try {
            const res = await fetch(App.url('todo/read'));
            const result = await res.json();
            const todos = result.data || result || [];

            const select = App.item('select');
            select.id = 'projects-todo-id';
            select.className = 'form-control';

            const defaultOpt = App.item('option');
            defaultOpt.value = '';
            defaultOpt.textContent = '-- Select Associated Todo --';
            select.appendChild(defaultOpt);

            todos.forEach(todo => {
                const opt = App.item('option');
                opt.value = todo.id;
                opt.textContent = todo.title || todo.name || `Todo #${todo.id}`;
                select.appendChild(opt);
            });

            return select;
        } catch (e) {
            App.log('Error fetching todos: ' + e.message);
            return null;
        }
    },

    submenu() {
        const recordsContainer = document.getElementById('projects-records');
        if (!recordsContainer) return;

        let submenuNav = document.getElementById('projects-submenu');
        if (!submenuNav) {
            submenuNav = App.item('div');
            submenuNav.id = 'projects-submenu';
            submenuNav.className = 'mb-3 btn-group';

            const filters = [
                { label: 'All Projects', status: '' },
                { label: 'Pending', status: 'pending' },
                { label: 'Completed', status: 'completed' }
            ];

            filters.forEach(f => {
                const btn = App.item('button');
                btn.type = 'button';
                btn.className = 'btn btn-secondary';
                btn.textContent = f.label;
                btn.onclick = () => this.read(f.status);
                submenuNav.appendChild(btn);
            });

            recordsContainer.parentNode.insertBefore(submenuNav, recordsContainer);
        }
    },

    async create(e) {
        e.preventDefault();

        const payload = {
            title: document.getElementById('projects-title')?.value.trim(),
            category: document.getElementById('projects-category')?.value.trim() || 'internal',
            description: document.getElementById('projects-desc')?.value.trim(),
            todo_id: document.getElementById('projects-todo-id')?.value || null,
            status: 'pending'
        };

        if (!payload.title) return;

        App.spinner();
        try {
            const res = await fetch(App.url('projects/create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status === 'success') {
                document.getElementById('projects-form').reset();
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    },

    async read(statusFilter = '') {
        const recordsDiv = document.getElementById('projects-records');
        if (!recordsDiv) return;

        App.spinner();
        try {
            const res = await fetch(App.url('projects/read'));
            const result = await res.json();

            recordsDiv.textContent = '';
            let data = result.data || [];

            if (statusFilter) {
                data = data.filter(item => item.status === statusFilter);
            }

            if (result.status !== 'success' || data.length === 0) {
                const emptyMsg = App.item('p');
                emptyMsg.textContent = 'No records found.';
                recordsDiv.appendChild(emptyMsg);
                return;
            }

            const table = App.item('table');
            table.className = 'table';

            const thead = App.item('thead');
            const headerRow = App.item('tr');
            ['Title', 'Category', 'Todo', 'Status', 'Actions'].forEach(text => {
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

                if (item.description) {
                    titleTd.appendChild(App.item('br'));
                    const descSmall = App.item('small');
                    descSmall.textContent = item.description;
                    titleTd.appendChild(descSmall);
                }

                const categoryTd = App.item('td');
                categoryTd.textContent = item.category || 'internal';

                const todoTd = App.item('td');
                todoTd.textContent = item.todo_title || item.todo_id || '-';

                const statusTd = App.item('td');
                statusTd.textContent = item.status;

                const actionsTd = App.item('td');

                const toggleBtn = App.item('button');
                toggleBtn.className = 'btn btn-sm btn-outline-primary me-2';
                toggleBtn.textContent = item.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
                toggleBtn.onclick = () => this.toggle(item.id, item.status);

                const deleteBtn = App.item('button');
                deleteBtn.className = 'btn btn-sm btn-outline-danger';
                deleteBtn.textContent = 'Delete';
                deleteBtn.onclick = () => this.delete(item.id);

                actionsTd.appendChild(toggleBtn);
                actionsTd.appendChild(deleteBtn);

                tr.append(titleTd, categoryTd, todoTd, statusTd, actionsTd);
                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            recordsDiv.appendChild(table);
        } finally {
            App.clearSpinner();
        }
    },

    async toggle(id, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        App.spinner();
        try {
            const res = await fetch(App.url(`projects/update/${id}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const result = await res.json();
            if (result.status === 'success') await this.read();
        } finally {
            App.clearSpinner();
        }
    },

    async delete(id) {
        if (!confirm('Delete record?')) return;
        App.spinner();
        try {
            const res = await fetch(App.url(`projects/delete/${id}`), {
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