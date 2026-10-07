const JobApplyController = {
    async hello() {
        const healthContainer = App.getItem('health');
        if (!healthContainer) return;

        let container = App.getItem('job-apply-output');
        if (!container) {
            container = App.item('div');
            container.id = 'job-apply-output';
            container.className = 'card shadow-sm border-0 mt-4 p-4';
            healthContainer.appendChild(container);
        }

        const h3 = App.item('h3');
        h3.className = 'fw-bold mb-3 fs-5';
        h3.textContent = 'Job Apply RAG Response';

        const p = App.item('p');
        p.className = 'text-muted';
        p.textContent = '[CHECKING...]';

        container.innerHTML = '';
        container.appendChild(h3);
        container.appendChild(p);

        App.spinner();

        try {

            // TODO Integrate JobApplyController.php functionality
            // to help the creation of a custom cv for each role
            // use rag to blend cv, job vacancy, personal statement, etc
            // Please be creative

            const data = await App.api('rag/rag/01_webdesigner');
            
            const pre = App.item('pre');
            pre.className = 'bg-light p-3 rounded mb-0';

            const code = App.item('code');
            code.textContent = JSON.stringify(data, null, 2);
            pre.appendChild(code);

            container.innerHTML = '';
            container.appendChild(h3);
            container.appendChild(pre);
        } catch (e) {
            p.className = 'text-danger';
            p.textContent = `Error loading RAG response: ${e.message}`;
        } finally {
            App.clearSpinner();
        }
    }
};