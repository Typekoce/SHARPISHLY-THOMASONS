const TodoController = {
    activeTaskId: null,
    tasks: [],

    async init() {
        const container = document.getElementById('todo-records');
        if (!container) return;

        let form = document.getElementById('todo-form');
        if (!form) {
            form = App.item('form');
            form.id = 'todo-form';
            form.className = 'mb-4';

            App.eForm?.(form, {
                'title': { id: 'todo-title', placeholder: 'Task title...' },
                'category': { id: 'todo-category', placeholder: 'Category...' },
                'description': { id: 'todo-desc', type: 'textarea', placeholder: 'Description...' }
            });

            const btn = App.item('button');
            btn.type = 'submit';
            btn.id = 'todo-submit-btn';
            btn.textContent = 'Add Task';
            form.appendChild(btn);

            form.onsubmit = (e) => this.save(e);
            container.parentNode.insertBefore(form, container);
        }

        this.submenu();
        await this.read();
    },

    submenu() {
        if (typeof SubMenuController === 'undefined') return;

        SubMenuController.menu([
            { label: 'All Tasks', action: () => this.read() },
            { label: 'Pending Tasks', action: () => this.read('', { status: 'pending' }) },
            { label: 'Completed Tasks', action: () => this.read('', { status: 'completed' }) },
            { label: 'Refresh', class: 'btn btn-sm btn-outline-primary', action: () => this.init() }
        ]);
    },

    async save(e) {
        e.preventDefault();

        const payload = {
            title: document.getElementById('todo-title')?.value.trim(),
            category: document.getElementById('todo-category')?.value.trim() || 'internal',
            description: document.getElementById('todo-desc')?.value.trim(),
            status: 'pending'
        };

        if (!payload.title) return App.flash?.('Please provide a task title.', 'warning');

        App.spinner?.();
        try {
            const endpoint = this.activeTaskId ? `todo/update/${this.activeTaskId}` : 'todo/create';
            const res = await fetch(App.url(endpoint), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();

            if (result.status === 'success' || result.result) {
                this.resetForm();
                App.flash?.(this.activeTaskId ? 'Task updated!' : 'Task created!', 'success');
                await this.read();
            } else {
                App.flash?.(result.message || 'Operation failed.', 'danger');
            }
        } catch (err) {
            App.flash?.('Network or server error.', 'danger');
        } finally {
            App.clearSpinner?.();
        }
    },

    goToNote(t) {
        const titleLink = App.item('a');
        const noteTargetId = t.note_id || t.id;
        titleLink.href = `#`;
        titleLink.textContent = t.title;
        titleLink.setAttribute('data-page','notes');
        titleLink.setAttribute('note_id',noteTargetId);
        titleLink.setAttribute('style','cursor:pointer;');       
        titleLink.onclick = (e) => {
            
            console.log('TodoController.goToNote', e);

            e.preventDefault();

            //App.flash?.(this.activeTaskId ? 'Task updated!' : 'Task created!', 'success');
            //App.setPageRegistry?.('activeNoteId', noteTargetId);
            //window.location.hash = `notes/${noteTargetId}`;
        };
        return titleLink;
    },

    async read(id = '', params = {}) {
        const recordsDiv = document.getElementById('todo-records');
        if (!recordsDiv) return;

        App.spinner?.();
        try {
            const endpoint = id ? `todo/read/${id}` : 'todo/read';
            const url = new URL(App.url(endpoint), window.location.origin);
            
            if (params && typeof params === 'object') {
                Object.entries(params).forEach(([k, v]) => {
                    if (v != null) url.searchParams.set(k, v);
                });
            }

            const res = await fetch(url.toString());
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();
            recordsDiv.textContent = '';

            const data = result.data || [];
            if (!data.length) {
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
            data.forEach(t => {
                const tr = App.item('tr');

                const titleTd = App.item('td');
                const titleStrong = App.item('strong');

                const titleLink = this.goToNote(t);

                titleStrong.appendChild(titleLink);
                titleTd.appendChild(titleStrong);

                if (t.description) {
                    titleTd.appendChild(App.item('br'));
                    const descSmall = App.item('small');
                    descSmall.textContent = t.description;
                    titleTd.appendChild(descSmall);
                }

                const categoryTd = App.item('td');
                categoryTd.textContent = t.category || 'internal';

                const statusTd = App.item('td');
                statusTd.textContent = t.status;

                const actionsTd = App.item('td');
                const select = App.item('select');
                select.className = 'form-select form-select-sm';

                const defaultOpt = App.item('option');
                defaultOpt.value = '';
                defaultOpt.textContent = 'Actions...';
                defaultOpt.disabled = true;
                defaultOpt.selected = true;
                select.appendChild(defaultOpt);

                const editOpt = App.item('option');
                editOpt.value = 'edit';
                editOpt.textContent = 'Edit';
                select.appendChild(editOpt);

                const toggleOpt = App.item('option');
                toggleOpt.value = 'toggle';
                toggleOpt.textContent = t.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
                select.appendChild(toggleOpt);

                const deleteOpt = App.item('option');
                deleteOpt.value = 'delete';
                deleteOpt.textContent = 'Delete';
                select.appendChild(deleteOpt);

                select.onchange = (e) => this.handleAction(e.target, t.id, t.status);
                actionsTd.appendChild(select);

                tr.appendChild(titleTd);
                tr.appendChild(categoryTd);
                tr.appendChild(statusTd);
                tr.appendChild(actionsTd);

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            recordsDiv.appendChild(table);

            this.tasks = data;
        } catch (err) {
            App.flash?.('Failed to fetch tasks.', 'danger');
        } finally {
            App.clearSpinner?.();
        }
    },

    handleAction(select, id, currentStatus) {
        const val = select.value;
        select.value = '';
        if (!val) return;

        if (val === 'edit') this.populateEdit(id);
        else if (val === 'toggle') this.toggle(id, currentStatus);
        else if (val === 'delete') this.delete(id);
    },

    populateEdit(id) {
        const task = this.tasks?.find(t => t.id == id);
        if (!task) return;

        this.activeTaskId = id;
        document.getElementById('todo-title').value = task.title || '';
        document.getElementById('todo-category').value = task.category || '';
        document.getElementById('todo-desc').value = task.description || '';
        
        const btn = document.getElementById('todo-submit-btn');
        if (btn) btn.textContent = 'Update Task';
    },

    resetForm() {
        this.activeTaskId = null;
        document.getElementById('todo-form')?.reset();
        const btn = document.getElementById('todo-submit-btn');
        if (btn) btn.textContent = 'Add Task';
    },

    async toggle(id, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        App.spinner?.();
        try {
            const res = await fetch(App.url(`todo/update/${id}`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            await this.read();
        } catch (err) {
            App.flash?.('Failed to update task status.', 'danger');
        } finally {
            App.clearSpinner?.();
        }
    },

    async delete(id) {
        if (!confirm('Delete task?')) return;
        App.spinner?.();
        try {
            const res = await fetch(App.url(`todo/delete/${id}`), { method: 'POST' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            await this.read();
        } catch (err) {
            App.flash?.('Failed to delete task.', 'danger');
        } finally {
            App.clearSpinner?.();
        }
    }
};