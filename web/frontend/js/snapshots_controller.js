/** snapshots_controller.js */
const SnapshotsController = {
    async save(payload) {
        app.spinner();
        try {
            const res = await fetch(App.url('ingestion/save'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status !== 'success') throw new Error(data.message);
            App.flash(`Success: ${data.id}`);
            app.modal.close();
        } catch(e) {
            App.flash(`Error: ${e.message}`);
        } finally {
            app.clearSpinner();
        }
    },

    createForm(agent) {
        console.log(agent);
        // 1. Create the container
        const container = document.getElementById('snapshots');
        // container.id = 'snapshots'; 

        // 3. Define schema matching your HTML requirements
        const schema = { 
            'URL': { 'id': 'url' }, 
            'Title': { 'id': 'title' }, 
            'Description': { 'id': 'desc', 'type': 'textarea' }
        };

        // 4. Use eForm to inject fields into container
        App.eForm(container, schema);

        // 5. Add Submit Button
        const btn = App.item('button');
        btn.className = 'btn btn-primary mt-2';
        btn.textContent = 'SCRAPE URL';
        btn.onclick = async function(){

        };
        container.appendChild(btn);

    }
};