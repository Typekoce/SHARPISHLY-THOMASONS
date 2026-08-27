const TodoController = {
    async init() {
        const recordsContainer = document.getElementById('todo-records');
        if (!recordsContainer) return;

        let form = document.getElementById('todo-form');
        if (!form) {
            form = App.item('form');
            form.id = 'todo-form';
            form.className = 'mb-4';

            const schema = {
                'title': { id: 'todo-title', placeholder: 'Task title...' },
                'category': { id: 'todo-category', placeholder: 'Category...' },
                'description': { id: 'todo-desc', type: 'textarea', placeholder: 'Description...' }
            };
            App.eForm(form, schema);

            const submitBtn = App.item('button');
            submitBtn.type = 'submit';
            submitBtn.textContent = 'Add Task';
            form.appendChild(submitBtn);

            form.onsubmit = (e) => this.create(e);

            recordsContainer.parentNode.insertBefore(form, recordsContainer);
        }

        this.submenu();
        await this.read();
    },

    submenu() {
        const menuItems = [
            {
                label: 'All Tasks',
                action: () => TodoController.read()
            },
            {
                label: 'Pending Tasks',
                action: () => TodoController.read('', { status: 'pending' })
            },
            {
                label: 'Completed Tasks',
                action: () => TodoController.read('', { status: 'completed' })
            },
            {
                label: 'Refresh',
                class: 'btn btn-sm btn-outline-primary me-2',
                action: () => TodoController.init()
            }
        ];

        App.dBug(menuItems);
        SubMenuController.menu(menuItems);
    },

    async create(e) {
        e.preventDefault();
        const titleEl = document.getElementById('todo-title');
        const categoryEl = document.getElementById('todo-category');
        const descEl = document.getElementById('todo-desc');

        const payload = {
            title: titleEl?.value.trim(),
            category: categoryEl?.value.trim() || 'internal',
            description: descEl?.value.trim(),
            status: 'pending'
        };

        if (!payload.title) {
            if (typeof App.flash === 'function') {
                App.flash('Please provide a task title.', 'warning');
            }
            return;
        }

        App.spinner();
        try {
            const res = await fetch(App.url('todo/create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status === 'success') {
                document.getElementById('todo-form').reset();
                if (typeof App.flash === 'function') {
                    App.flash('Task created successfully!', 'success');
                }
                await this.read();
            } else {
                if (typeof App.flash === 'function') {
                    App.flash(result.message || 'Failed to create task.', 'danger');
                }
            }
        } catch (err) {
            if (typeof App.flash === 'function') {
                App.flash('Network or server error during creation.', 'danger');
            }
        } finally {
            App.clearSpinner();
        }
    },

    async read(id = '', params = {}) {
        const recordsDiv = document.getElementById('todo-records');
        if (!recordsDiv) return;

        App.spinner();
        try {
            const endpoint = id ? `todo/read/${id}` : 'todo/read';
            const res = await fetch(App.url(endpoint));
            const result = await res.json();

            recordsDiv.textContent = '';

            let data = result.data || [];

            if (params.status) {
                data = data.filter(task => task.status === params.status);
            }

            if (result.status !== 'success' || data.length === 0) {
                const emptyMsg = App.item('p');
                emptyMsg.textContent = 'No tasks found.';
                recordsDiv.appendChild(emptyMsg);
                return;
            }

            const table = App.item('table');
            table.className = 'table';

            const thead = App.item('thead');
            const headerRow = App.item('tr');
            ['Title', 'Category', 'Status', 'Actions'].forEach(text => {
                const th = App.item('th');
                th.textContent = text;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = App.item('tbody');
            data.forEach(task => {
                const tr = App.item('tr');

                const titleTd = App.item('td');
                const titleStrong = App.item('strong');
                titleStrong.textContent = task.title;
                titleTd.appendChild(titleStrong);

                if (task.description) {
                    titleTd.appendChild(App.item('br'));
                    const descSmall = App.item('small');
                    descSmall.textContent = task.description;
                    titleTd.appendChild(descSmall);
                }

                const categoryTd = App.item('td');
                categoryTd.textContent = task.category || 'internal';

                const statusTd = App.item('td');
                statusTd.textContent = task.status;

                const actionsTd = App.item('td');

                const dropdown = App.item('select');
                dropdown.className = 'form-select form-select-sm';

                const defaultOpt = App.item('option');
                defaultOpt.textContent = 'Actions...';
                defaultOpt.value = '';
                defaultOpt.disabled = true;
                defaultOpt.selected = true;
                dropdown.appendChild(defaultOpt);

                const toggleOpt = App.item('option');
                toggleOpt.textContent = task.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
                toggleOpt.value = 'toggle';
                dropdown.appendChild(toggleOpt);

                const deleteOpt = App.item('option');
                deleteOpt.textContent = 'Delete';
                deleteOpt.value = 'delete';
                dropdown.appendChild(deleteOpt);

                dropdown.onchange = (e) => {
                    const val = e.target.value;
                    e.target.value = ''; 
                    if (val === 'toggle') {
                        this.toggle(task.id, task.status);
                    } else if (val === 'delete') {
                        this.delete(task.id);
                    }
                };

                actionsTd.appendChild(dropdown);

                tr.appendChild(titleTd);
                tr.appendChild(categoryTd);
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

    async toggle(id, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        App.spinner();
        try {
            const res = await fetch(App.url(`todo/update/${id}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const result = await res.json();

            if (result.status === 'success') {
                if (typeof App.flash === 'function') {
                    App.flash(`Task status updated to ${newStatus}.`, 'info');
                }
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    },

    async delete(id) {
        if (!confirm('Delete task?')) return;
        App.spinner();
        try {
            const res = await fetch(App.url(`todo/delete/${id}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await res.json();

            if (result.status === 'success') {
                if (typeof App.flash === 'function') {
                    App.flash('Task deleted.', 'warning');
                }
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    }
};