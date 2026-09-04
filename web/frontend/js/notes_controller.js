const NotesController = {
    currentNoteId: null,

    async init() {
        console.log('NotesController.init');
        App.dBug({ context: 'NotesController.init', div: 'notes-records' });

        const recordsContainer = document.getElementById('notes-records');
        if (!recordsContainer) return;

        let form = document.getElementById('notes-form');
        if (!form) {
            form = App.item('form');
            form.id = 'notes-form';
            form.className = 'mb-4';

            const schema = {
                'title': { id: 'notes-title', placeholder: 'Title...' },
                'category': { id: 'notes-category', placeholder: 'Category...' },
                'description': { id: 'notes-desc', type: 'textarea', placeholder: 'Description...' }
            };
            App.eForm(form, schema);

            const submitBtn = App.item('button');
            submitBtn.type = 'submit';
            submitBtn.textContent = 'Add Entry';
            form.appendChild(submitBtn);

            form.onsubmit = (e) => this.create(e);

            recordsContainer.parentNode.insertBefore(form, recordsContainer);
        }

        this.submenu();

        // Check for local noteId or retrieve from TodoController if available
        const activeNoteId = this.getNoteId();
        if (activeNoteId) {
            await this.read(activeNoteId);
        } else {
            await this.read();
        }
    },

    setNoteId(id) {
        this.currentNoteId = id;
    },

    getNoteId() {
        if (this.currentNoteId) {
            return this.currentNoteId;
        }

        if (typeof TodoController !== 'undefined' && TodoController.getNoteId) {
            const todoNoteId = TodoController.getNoteId();
            if (todoNoteId) {
                this.setNoteId(todoNoteId);
                return todoNoteId;
            }
        }

        App.flash('No note id present', 'error');
        return null;
    },

    submenu() {
        if (typeof SubMenuController === 'undefined') return;

        const menuItems = [
            { label: 'All Notes', action: () => this.read() },
            { label: 'Pending Notes', action: () => this.read('', { status: 'pending' }) },
            { label: 'Completed Notes', action: () => this.read('', { status: 'completed' }) },
            { label: 'Refresh', class: 'btn btn-sm btn-outline-primary', action: () => this.init() }
        ];

        console.log('NotesController.submenu', menuItems);
        App.dBug({ context: 'NotesController.submenu', menuItems, div: 'notes-records' });
        SubMenuController.menu(menuItems);
    },
async create(e) {
    e.preventDefault();
    const titleEl = document.getElementById('notes-title');
    const categoryEl = document.getElementById('notes-category');
    const descEl = document.getElementById('notes-desc');

    const activeTodoId = this.getNoteId() || 1;

    const payload = {
        todo_id: activeTodoId,
        title: titleEl?.value.trim(),
        note: descEl?.value.trim() || titleEl?.value.trim(),
        category: categoryEl?.value.trim() || 'internal',
        description: descEl?.value.trim(),
        status: 'pending'
    };

    console.log('NotesController.create:payload', payload);
    App.dBug({ context: 'NotesController.create', payload, div: 'notes-records' });

    if (!payload.title) return;

    App.spinner();
    try {
        const res = await fetch(App.url('notes/create'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        console.log('NotesController.create:response', result);
        App.dBug({ context: 'NotesController.create:response', result, div: 'notes-records' });

        if (result.status === 'success') {
            document.getElementById('notes-form').reset();
            await this.read();
        }
    } catch (err) {
        console.error('NotesController.create:error', err);
        App.dBug({ context: 'NotesController.create:error', error: err.message, div: 'notes-records' });
    } finally {
        App.clearSpinner();
    }
},
    renderDisplay(recordsDiv, data) {
        if (!data || data.length === 0) return;

        const test = App.item('div');
        test.textContent = data[0].note;

        recordsDiv.appendChild(test);

        console.log('NotesController.renderDisplay', { note: data[0].note });
    },

    async read(id = '', params = {}) {
        const recordsDiv = document.getElementById('notes-records');
        if (!recordsDiv) return;

        const endpoint = id ? `notes/read/${id}` : 'notes/read';

        console.log('NotesController.read', { endpoint, id, params });
        App.dBug({ context: 'NotesController.read', endpoint, id, params, div: 'notes-records' });

        App.spinner();
        try {
            const res = await fetch(App.url(endpoint));
            const result = await res.json();

            console.log('NotesController.read:response', result);
            App.dBug({ context: 'NotesController.read:response', result, div: 'notes-records' });

            recordsDiv.textContent = '';

            let rawData = result.data ?? [];
            let data = Array.isArray(rawData) ? rawData : [rawData];

            data = data.map(item => {
                if (!item || typeof item !== 'object') return item;

                const keys = Object.keys(item);
                if (keys.length === 1 && item.title === undefined) {
                    const inner = item[keys[0]];
                    if (inner && typeof inner === 'object') {
                        return inner;
                    }
                }
                return item;
            });

            data = data.filter(item =>
                item &&
                typeof item === 'object' &&
                Object.keys(item).length > 0 &&
                item.title !== undefined
            );

            if (params.status) {
                data = data.filter(item => item.status === params.status);
            }

            console.log('NotesController.read:normalized', { rawData, data });
            App.dBug({ context: 'NotesController.read:normalized', rawData, data, div: 'notes-records' });

            this.renderDisplay(recordsDiv, data);

            if (result.status !== 'success' || data.length === 0) {
                const emptyMsg = App.item('p');
                emptyMsg.textContent = 'No records found.';
                recordsDiv.appendChild(emptyMsg);
                return;
            }

            if (id) {
                const backBtn = App.item('button');
                backBtn.className = 'btn btn-sm btn-secondary mb-3';
                backBtn.textContent = '← Back to All Notes';
                backBtn.onclick = () => {
                    this.setNoteId(null);
                    this.read();
                };
                recordsDiv.appendChild(backBtn);
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
            data.forEach(item => {
                tbody.appendChild(this.buildRow(item));
            });

            table.appendChild(tbody);
            recordsDiv.appendChild(table);
        } catch (err) {
            console.error('NotesController.read:error', err);
            App.dBug({ context: 'NotesController.read:error', error: err.message, div: 'notes-records' });
        } finally {
            App.clearSpinner();
        }
    },

    buildRow(item) {
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

        const statusTd = App.item('td');
        statusTd.textContent = item.status;

        const actionsTd = App.item('td');
        const select = App.item('select');
        select.className = 'form-select form-select-sm';

        const defaultOpt = App.item('option');
        defaultOpt.value = '';
        defaultOpt.textContent = 'Actions...';
        defaultOpt.disabled = true;
        defaultOpt.selected = true;
        select.appendChild(defaultOpt);

        const displayOpt = App.item('option');
        displayOpt.value = 'display';
        displayOpt.textContent = 'Display';
        select.appendChild(displayOpt);

        const toggleOpt = App.item('option');
        toggleOpt.value = 'toggle';
        toggleOpt.textContent = item.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
        select.appendChild(toggleOpt);

        const deleteOpt = App.item('option');
        deleteOpt.value = 'delete';
        deleteOpt.textContent = 'Delete';
        select.appendChild(deleteOpt);

        select.onchange = (e) => this.handleAction(e.target, item.id, item.status);
        actionsTd.appendChild(select);

        tr.appendChild(titleTd);
        tr.appendChild(categoryTd);
        tr.appendChild(statusTd);
        tr.appendChild(actionsTd);

        return tr;
    },

    handleAction(select, id, currentStatus) {
        const val = select.value;
        select.value = '';
        if (!val) return;

        console.log('NotesController.handleAction', { action: val, id, currentStatus });
        App.dBug({ context: 'NotesController.handleAction', action: val, id, currentStatus, div: 'notes-records' });

        if (val === 'display') this.display(id);
        else if (val === 'toggle') this.toggle(id, currentStatus);
        else if (val === 'delete') this.delete(id);
    },

    async display(id) {
        console.log('NotesController.display', { id });
        App.dBug({ context: 'NotesController.display', id, div: 'notes-records' });
        this.setNoteId(id);
        await this.read(id);
    },

    async toggle(id, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        const endpoint = `notes/update/${id}`;

        console.log('NotesController.toggle', { id, currentStatus, newStatus, endpoint });
        App.dBug({ context: 'NotesController.toggle', id, currentStatus, newStatus, endpoint, div: 'notes-records' });

        App.spinner();
        try {
            const res = await fetch(App.url(endpoint), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const result = await res.json();

            console.log('NotesController.toggle:response', result);
            App.dBug({ context: 'NotesController.toggle:response', result, div: 'notes-records' });

            if (result.status === 'success') await this.read();
        } catch (err) {
            console.error('NotesController.toggle:error', err);
            App.dBug({ context: 'NotesController.toggle:error', error: err.message, div: 'notes-records' });
        } finally {
            App.clearSpinner();
        }
    },

    async delete(id) {
        if (!confirm('Delete record?')) return;
        const endpoint = `notes/delete/${id}`;

        console.log('NotesController.delete', { id, endpoint });
        App.dBug({ context: 'NotesController.delete', id, endpoint, div: 'notes-records' });

        App.spinner();
        try {
            const res = await fetch(App.url(endpoint), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await res.json();

            console.log('NotesController.delete:response', result);
            App.dBug({ context: 'NotesController.delete:response', result, div: 'notes-records' });

            if (result.status === 'success') await this.read();
        } catch (err) {
            console.error('NotesController.delete:error', err);
            App.dBug({ context: 'NotesController.delete:error', error: err.message, div: 'notes-records' });
        } finally {
            App.clearSpinner();
        }
    }
};