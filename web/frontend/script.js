const App = {
    async loadTemplate(url, data = {}) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`Missing: ${url}`);
            let template = await res.text();
            
            // Lateral Thinking: Recursive path resolver for {{ object.key }}
            return template.replace(/{{\s*([\w.]+)\s*}}/g, (_, path) => {
                const value = path.split('.').reduce((obj, key) => obj?.[key], data);
                if (typeof value === 'boolean') return value ? '<span class="text-success">ONLINE</span>' : '<span class="text-danger">OFFLINE</span>';
                return value ?? '';
            });
        } catch (e) {
            return `<div class="p-4 text-danger">Template Error: ${e.message}</div>`;
        }
    },
    crm() { Model.currentPage = 'home'; Controller.render(); },
    cyberdeck() { Model.currentPage = 'llm'; Controller.render(); }
};

const Model = {
    queue: [],
    healthStatus: null,
    currentPage: 'home'
};

const Controller = {

    autoFill(){
	var input = document.getElementById('autocomplete');
	var ul = document.createElement('ul');
	var li = document.createElement('li');
	li.innerHTML="test";
	ul.appendChild(li);
	input.appendChild(ul);
    },


    async getQueries(input) {
            try {
                //const res = await fetch(`http://192.168.0.22:8765/rag/ask?query=What%20is%20Ste>
                const res = await fetch(`http://192.168.0.22/php/query`);
                const data = await res.json();
		console.log(data);
		this.autoFill();
                //history.innerHTML += `<p><strong>Bot:</strong> ${data.answer || data.message}</p>>
            } catch (e) {
                //history.innerHTML += `<p class="text-danger">Error: Service unavailable</p>`;
            }
    },

    async bindRag() {
        const btn = document.getElementById('rag-send');
        const input = document.getElementById('rag-input');
        const history = document.getElementById('chat-history');

        if (!btn) return;

        btn.onclick = async () => {
            //TODO: Add autocomplete drop down
            this.getQueries(input);
            const query = input.value.trim();
            if (!query) return;

            history.innerHTML += `<p><strong>You:</strong> ${query}</p>`;
            input.value = '';

            try {
		//const res = await fetch(`http://192.168.0.22:8765/rag/ask?query=What%20is%20Steve%20Austin%27s%20email%3F`);
                const res = await fetch(`http://192.168.0.22/php/rag/chat/?query=${encodeURIComponent(query)}`);
                const data = await res.json();
                history.innerHTML += `<p><strong>Bot:</strong> ${data.answer || data.message}</p>`;
            } catch (e) {
                history.innerHTML += `<p class="text-danger">Error: Service unavailable</p>`;
            }
            history.scrollTop = history.scrollHeight;
        };
    },

    // Crucial: Async init enforces synchronous bootstrapping execution order
    async init() {
        const host = window.location.hostname;
        const sub = host.split('.')[0];

        try {
            // Force the layout engine to completely parse header.html before evaluating nav links
            const res = await fetch('views/layouts/header.html');
            if (res.ok) {
                let headerTemplate = await res.text();
                
                // Process initial h1 template tags matching the initial routing state
                const initialH1 = (sub === 'cyberdeck') ? 'Cyberdeck (LLM)' : 'Thomasons V3';
                document.getElementById('header').innerHTML = headerTemplate.replace(/{{\s*h1\s*}}/g, initialH1);
            }
        } catch (e) { 
            console.error("Layout Engine initialization failed:", e); 
        }

        // Subdomain router execution loop
        if (sub === 'crm') App.crm();
        else if (sub === 'cyberdeck') App.cyberdeck();
        else this.navigate('home');

        document.addEventListener('click', (e) => {
            const page = e.target.closest('[data-page]')?.dataset.page;
            if (page) {
                e.preventDefault();
                this.navigate(page);
            }
        });
	//TODO: Only display on health page
        //setInterval(() => this.fetchHealth(), 5000);
    },

    async handleUpload(files) {
        if (!files.length) return;
        const formData = new FormData();
        Array.from(files).forEach(f => {
            formData.append('documents[]', f);
            Model.queue.push({ name: f.name, status: 'processing', progress: 25 });
        });
        this.render();
        
        try {
            const res = await fetch('/php/job/create', { method: 'POST', body: formData });
            const result = await res.json();
            // Update queue status from NATS response loop
            Model.queue.forEach(item => { item.status = 'queued'; item.progress = 50; });
            this.render();
        } catch (e) { console.error("Upload failed"); }
    },

    bindEvents() {
        const drop = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');
        if (!drop || !input) return;

        drop.onclick = () => input.click();
        input.onchange = (e) => this.handleUpload(e.target.files);
        drop.ondragover = (e) => { e.preventDefault(); drop.style.backgroundColor = '#f0f7ff'; };
        drop.ondragleave = () => drop.style.backgroundColor = '';
        drop.ondrop = (e) => {
            e.preventDefault();
            drop.style.backgroundColor = '';
            this.handleUpload(e.dataTransfer.files);
        };
    },

    async old_fetchHealth() {
        try {
            const res = await fetch('/php/health');
            Model.healthStatus = await res.json();
            if (Model.currentPage === 'health' || Model.currentPage === 'llm') this.render();
        } catch (e) { console.error("Monitor Offline"); }
    },

    navigate(page) {
        Model.currentPage = page;
        this.render();
    },

   async docs(page) {
	if(page === 'docs') {
		alert('this is the fucking docs page!!!!');

		try {
	
			const res = await fetch('http://192.168.0.22/php/docs');

			console.log(res);

			const data = await res.json();
               		

			 console.log(data);

		} catch (e) {
	
			console.error("Request error",e); 

		}
	}
    },

    async render() {
        const target = document.getElementById('app');
        if (!target) return;

        // Path resolution separation
        const templatePath = Model.currentPage === 'home' 
            ? 'views/home/index.html' 
            : `views/pages/${Model.currentPage}.html`;

        let html = await App.loadTemplate(templatePath, {
            health: Model.healthStatus,
            queue: Model.queue
        });

        // Update the template layout header
        const headerH1 = document.querySelector('#header h1');
        if (headerH1) {
            headerH1.textContent = Model.currentPage === 'home' ? 'Thomasons V3' : Model.currentPage.toUpperCase();
        }

        // Toggles active navigation items
        document.querySelectorAll('.navbar .nav-link').forEach(link => {
            link.classList.toggle('active', link.dataset.page === Model.currentPage);
        });


        // Docs rendering
	this.docs(Model.currentPage);

        // Queue rendering
        if (Model.currentPage === 'llm') {
            const itemsHtml = Model.queue.map(item => `
                <div class="card mb-2 p-2 small border-0 shadow-sm">
                    <div class="d-flex justify-content-between">
                        <strong>${item.name}</strong>
                        <span class="text-primary">${item.status}</span>
                    </div>
                    <div class="progress mt-1" style="height:4px">
                        <div class="progress-bar" style="width:${item.progress}%"></div>
                    </div>
                </div>
            `).join('');
            html = html.replace('', itemsHtml || '<p class="text-muted small">No active jobs.</p>');
        }

        target.innerHTML = `<div class="fade-in">${html}</div>`;
        
        // Final Event Binding
        this.bindEvents();
        if (Model.currentPage === 'rag') this.bindRag();
    }
};

document.addEventListener('DOMContentLoaded', () => Controller.init());
