/**
 * Render: View-specific UI Logic
 */
const Render = {
    selectList(fields, callback) {
        const select = document.createElement('select');
        select.className = 'agent-selector';
        const defaultOption = document.createElement('option');
        defaultOption.text = 'Select an agent...';
        defaultOption.value = '';
        select.appendChild(defaultOption);
        fields.forEach(field => {
            const option = document.createElement('option');
            option.value = field.id;
            option.text = field.name;
            select.appendChild(option);
        });
        select.onchange = function(e) { if (this.value) callback(this.value); };
        return select;
    },
    eForm(form, fields) {
        for (let field in fields) {
            const input = App.item('input');
            input.setAttribute('id', field);
            const label = App.item('label');
            label.innerHTML = field;
            const row = App.item('div');
            row.appendChild(label);
            row.appendChild(input);
            form.appendChild(row);
        }
    },
    renderQueue() {
        const queueContainer = document.getElementById('queue-container');
        if (!queueContainer) return;
        queueContainer.innerHTML = Model.queue.map(item => `
            <div class="card mb-2 p-2 small border-0 shadow-sm">
                <div class="d-flex justify-content-between">
                    <strong>${item.name}</strong>
                    <span class="text-primary">${item.status}</span>
                </div>
            </div>
        `).join('');
    }
};
