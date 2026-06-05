const view = {
    neuralPipeline() {
        const container = document.getElementById('neural-pipeline');
        if (!container) return;
        container.innerHTML = '';
        const np = App.item('div');
        np.className = 'pipeline-list';
        Model.queue.forEach(item => {
            const row = App.item('div');
            row.className = 'card mb-2 p-2 small border-0 shadow-sm';
            row.innerHTML = `<div class="d-flex justify-content-between"><strong>${item.name}</strong><span class="text-primary">${item.status}</span></div>`;
            np.appendChild(row);
        });
        container.appendChild(np);
    }
};
