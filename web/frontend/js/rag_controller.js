Object.assign(Controller, {
    async bindRag() {
        const btn = document.getElementById('rag-send');
        const input = document.getElementById('rag-input');
        const history = document.getElementById('chat-history');
        if (!btn) return;
        btn.onclick = async () => {
            const query = input.value.trim();
            if (!query) return;
            history.innerHTML += `<p><strong>You:</strong> ${query}</p>`;
            input.value = '';
            try {
                const res = await fetch(App.url('rag/chat/'), {
                    method: 'POST',
                    body: JSON.stringify({ query })
                });
                const data = await res.json();
                history.innerHTML += `<p><strong>Bot:</strong> ${data.answer || data.message}</p>`;
            } catch (e) { history.innerHTML += `<p class="text-danger">Error: Service unavailable</p>`; }
            history.scrollTop = history.scrollHeight;
        };
    }
});
