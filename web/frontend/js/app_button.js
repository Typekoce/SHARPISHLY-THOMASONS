/** app_button.js */
App.button = function(name,func){
    PageRegistry[name] = func;
};

App.eButtons = function(container, schema) {
    if (!container || !schema) return;

    Object.entries(schema).forEach(([key, config]) => {
        const btn = App.item('button');
        btn.id = config.id || `btn-${key}`;
        btn.className = config.class || 'btn btn-primary mb-2';
        btn.textContent = config.label || key.toUpperCase();
        btn.type = config.type || 'button';

        if (typeof config.action === 'function') {
            btn.onclick = config.action;
        }

        container.appendChild(btn);
    });
};
