const NotesController = {
    async init() {
        App.refresh?.({ context: this });

        const container = document.getElementById('notes-records');
        if (!container) return;

        let form = document.getElementById('notes-form');
        if (!form) {
            form = App.item('form');
            form.id = 'notes-form';
            form.className = 'mb-4';

            App.eForm?.(form, {
                'title': { id: 'note-title', placeholder: 'Note title...' },
                'content': { id: 'note-content', type: 'textarea', placeholder: 'Write note here...' }
            });

            const btn = App.item('button');
            btn.type = 'submit';
            btn.id = 'note-submit-btn';
            btn.textContent = 'Save Note';
            form.appendChild(btn);

            form.onsubmit = (e) => this.save(e);
            container.parentNode.insertBefore(form, container);
        }

        this.submenu();

        // 1. Read incoming target payload from global App.data
        const noteData = App.getData?.('note-details') || null;
        const activeId = noteData?.id || null;

        // 2. Display associated TODO title if present
        const titleHeader = document.getElementById('notes-todo-title');
        if (titleHeader) {
            titleHeader.textContent = noteData?.title ? `Task: ${noteData.title}` : '';
        }

        // 3. Always perform fresh fetch from server
        await this.read(activeId);
    },

    submenu() {
        if (typeof SubMenuController === 'undefined') return;

        SubMenuController.menu([
            { label: 'All Notes', action: () => this.read() },
            { label: 'Refresh', class: 'btn btn-sm btn-outline-primary', action: () => this.init() }
        ]);
    },

    getTodoTitle() {
        const noteData = App.getData?.('note-details');
        return noteData?.title || '';
    },

    async save(e) {
        e.preventDefault();

        const noteData = App.getData?.('note-details');
        const payload = {
            todo_id: noteData?.id || null,
            title: document.getElementById('note-title')?.value.trim(),
            content: document.getElementById('note-content')?.value.trim()
        };

        if (!payload.title || !payload.content) {
            return App.flash?.('Please provide both a title and content.', 'warning');
        }

        App.spinner?.();
        try {
            const res = await fetch(App.url('notes/create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();

            if (result.status === 'success' || result.result) {
                this.resetForm();
                App.flash?.('Note saved!', 'success');
                await this.read(payload.todo_id);
            } else {
                App.flash?.(result.message || 'Failed to save note.', 'danger');
            }
        } catch (err) {
            App.flash?.('Network or server error.', 'danger');
        } finally {
            App.clearSpinner?.();
        }
    },

    async read(id = '') {
        const container = document.getElementById('notes-records');
        if (!container) return;

        App.spinner?.();
        try {
            const endpoint = id ? `notes/read/${id}` : 'notes/read';
            const res = await fetch(App.url(endpoint));

            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const result = await res.json();

            this.renderNotes(result.data || []);
        } catch (err) {
            App.flash?.('Failed to fetch notes.', 'danger');
        } finally {
            App.clearSpinner?.();
        }
    },

    renderNotes(data) {
        const recordsDiv = document.getElementById('notes-records');
        if (!recordsDiv) return;

        recordsDiv.textContent = '';

        if (!data || data.length === 0) {
            const emptyMsg = App.item('p');
            emptyMsg.textContent = 'No notes found.';
            recordsDiv.appendChild(emptyMsg);
            return;
        }

        const listGroup = App.item('div');
        listGroup.className = 'list-group';

        data.forEach(note => {
            const item = App.item('div');
            item.className = 'list-group-item';

            const header = App.item('h5');
            header.className = 'mb-1';
            header.textContent = note.title;

            const body = App.item('p');
            body.className = 'mb-1';
            body.textContent = note.content;

            item.appendChild(header);
            item.appendChild(body);

            if (note.created_at) {
                const footer = App.item('small');
                footer.className = 'text-muted';
                footer.textContent = `Created: ${note.created_at}`;
                item.appendChild(footer);
            }

            listGroup.appendChild(item);
        });

        recordsDiv.appendChild(listGroup);
    },

    resetForm() {
        document.getElementById('notes-form')?.reset();
    }
};