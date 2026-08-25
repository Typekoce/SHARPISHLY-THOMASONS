const TodoController = {
    async init() {
        const container = App.getItem('todo');
        if (!container) return;

        container.textContent = '';

        const heading = App.item('h2');
        heading.textContent = 'Todo Manager';
        container.appendChild(heading);

        const form = App.item('form');
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
        container.appendChild(form);

        const listDiv = App.item('div');
        listDiv.id = 'todo-list';
        container.appendChild(listDiv);

        await this.read();
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

        if (!payload.title) return;

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
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    },

    async read() {
        const listDiv = document.getElementById('todo-list');
        if (!listDiv) return;

        App.spinner();
        try {
            const res = await fetch(App.url('todo/read'));
            const result = await res.json();

            listDiv.textContent = '';

            if (result.status !== 'success' || !result.data || result.data.length === 0) {
                const emptyMsg = App.item('p');
                emptyMsg.textContent = 'No tasks found.';
                listDiv.appendChild(emptyMsg);
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
            result.data.forEach(task => {
                const tr = App.item('tr');

                // Title & Description
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

                // Category
                const categoryTd = App.item('td');
                categoryTd.textContent = task.category || 'internal';

                // Status
                const statusTd = App.item('td');
                statusTd.textContent = task.status;

                // Actions
                const actionsTd = App.item('td');

                const toggleBtn = App.item('button');
                toggleBtn.textContent = task.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
                toggleBtn.onclick = () => this.toggle(task.id, task.status);

                const deleteBtn = App.item('button');
                deleteBtn.textContent = 'Delete';
                deleteBtn.onclick = () => this.delete(task.id);

                actionsTd.appendChild(toggleBtn);
                actionsTd.appendChild(deleteBtn);

                tr.appendChild(titleTd);
                tr.appendChild(categoryTd);
                tr.appendChild(statusTd);
                tr.appendChild(actionsTd);

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            listDiv.appendChild(table);
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
            if (result.status === 'success') await this.read();
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
            if (result.status === 'success') await this.read();
        } finally {
            App.clearSpinner();
        }
    },
};