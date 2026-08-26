const ShopsController = {
    async init() {
        const recordsContainer = document.getElementById('shops-records');
        if (!recordsContainer) return;

        let form = document.getElementById('shops-form');
        if (!form) {
            form = App.item('form');
            form.id = 'shops-form';
            form.className = 'mb-4';

            const schema = {
                'title': { id: 'shops-title', placeholder: 'Title...' },
                'category': { id: 'shops-category', placeholder: 'Category...' },
                'description': { id: 'shops-desc', type: 'textarea', placeholder: 'Description...' }
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
        await this.read();
    },

    async create(e) {
        e.preventDefault();
        const titleEl = document.getElementById('shops-title');
        const categoryEl = document.getElementById('shops-category');
        const descEl = document.getElementById('shops-desc');

        const payload = {
            title: titleEl?.value.trim(),
            category: categoryEl?.value.trim() || 'internal',
            description: descEl?.value.trim(),
            status: 'pending'
        };

        if (!payload.title) return;

        App.spinner();
        try {
            const res = await fetch(App.url('shops/create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status === 'success') {
                document.getElementById('shops-form').reset();
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    },

    async read(id = '', params = {}) {
        const recordsDiv = document.getElementById('shops-records');
        if (!recordsDiv) return;

        App.spinner();
        try {
            const endpoint = id ?  : 'shops/read';
            const res = await fetch(App.url(endpoint));
            const result = await res.json();

            recordsDiv.textContent = '';

            let data = result.data || [];

            if (params.status) {
                data = data.filter(item => item.status === params.status);
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
            ['Title', 'Category', 'Status', 'Actions'].forEach(text => {
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

                const statusTd = App.item('td');
                statusTd.textContent = item.status;

                const actionsTd = App.item('td');

                const toggleBtn = App.item('button');
                toggleBtn.textContent = item.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
                toggleBtn.onclick = () => this.toggle(item.id, item.status);

                const deleteBtn = App.item('button');
                deleteBtn.textContent = 'Delete';
                deleteBtn.onclick = () => this.delete(item.id);

                actionsTd.appendChild(toggleBtn);
                actionsTd.appendChild(deleteBtn);

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
            const res = await fetch(App.url(), {
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
            const res = await fetch(App.url(), {
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
