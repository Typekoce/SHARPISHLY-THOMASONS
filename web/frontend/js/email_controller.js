Object.assign(Controller, {
    eFields(fields) {
        let post = {};
        for (let field in fields) {
            const el = document.getElementById(field);
            post[field] = el ? el.value : '';
        }
        return post;
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
    async bindEmails() {
        const fields = { 'to': {}, 'subject': {}, 'body': {} };
        const form = document.getElementById('form');
        if (!form) return;
        this.eForm(form, fields);
        const btn = App.item('div');
        btn.className = 'btn btn-outline-primary mt-2';
        btn.innerHTML = "Queue Email Task";
        form.appendChild(btn);
        btn.onclick = async () => {
            const taskData = this.eFields(fields);
            taskData.id = Date.now();
            try {
                const res = await fetch(App.url('emails/queue/'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(taskData)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    App.flash("Email Task Queued: " + result.id);
                    form.reset();
                }
            } catch (e) { App.flash("Critical Error: Persistence failed"); }
        };
    },
    prepareEmailFormForAgent(agent) {
        this.navigate('emails');
        setTimeout(() => {
            const subjectInput = document.getElementById('subject');
            if (subjectInput) subjectInput.value = `Task via ${agent.agent_name}`;
            const form = document.getElementById('form');
            const agentCtx = App.item('input');
            agentCtx.type = 'hidden';
            agentCtx.id = 'agent_context';
            agentCtx.value = agent.id;
            form.appendChild(agentCtx);
            App.flash(`Composing email for Agent: ${agent.agent_name}`);
        }, 100);
    }
});
