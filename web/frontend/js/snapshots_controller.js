/** snapshots_controller.js */
const SnapshotController = {
    async bindEmails() {
        alert('Inside of bindEmails');
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

        btn.onclick = (e) => {
            e.preventDefault();
            
            const toInput = document.getElementById('to');
            const subjectInput = document.getElementById('subject');
            const bodyInput = document.getElementById('body');
            
            // Use the reverted, clean emailQueue
            AgentController.emailQueue(toInput, subjectInput, bodyInput);
        };
    },
};