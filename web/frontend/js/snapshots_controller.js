/** snapshots_controller.js */
const SnapshotController = {
    async displayForm() {

        const fields = { 
            'url': {}, 
            'description': {}, 
            'page': {} 
        };
        const form = document.getElementById('form');
        if (!form) return;
        
        // Clear the form and re-render only the fields
        form.innerHTML = '';
        Controller.eForm(form, fields);

        const btn = App.item('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-primary mt-2';
        btn.innerHTML = "Queue Email Task";
        form.appendChild(btn);

        btn.onclick = async (e) => {
            e.preventDefault();
            
            // Use the existing Controller utility to capture all form inputs
            // This assumes the fields match the keys in your SnapshotController.bindEmails schema
            const postData = {
                url: document.getElementById('url')?.value,
                description: document.getElementById('description')?.value,
                page: document.getElementById('page')?.value
            };

            app.spinner();

            try {
                const res = await fetch(App.url('ingestion/test'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(postData)
                });

                const result = await res.json();

                console.log(result);

                if (res.ok) {
                    App.flash('Success: Snapshot queued with ID ' + (result.id || 'N/A'));
                } else {
                    throw new Error(result.message || 'Server returned an error');
                }

                
            } catch (err) {
                App.flash('Error: ' + err.message);
                console.error('Snapshot POST error:', err);
            } finally {
                app.clearSpinner();
            }
        };
    },
};