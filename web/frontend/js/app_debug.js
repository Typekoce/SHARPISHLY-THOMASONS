/**
 * Enhanced App Debug Utility
 * @param {any} data - Message, Object, or Error to inspect
 * @param {string} [label='DEBUG'] - Context tag/label
 * @param {string} [level='log'] - Console level: 'log', 'info', 'warn', 'error', 'table'
 */
App.debug = function (data, label = 'DEBUG', level = 'log') {
    const timestamp = new Date().toTimeString().split(' ')[0];
    const prefix = `[${timestamp}] [${label}]`;

    // 1. Structured Console Grouping
    if (typeof console[level] === 'function') {
        console.groupCollapsed(`%c${prefix}`, 'color: #007acc; font-weight: bold;', 
            typeof data === 'string' ? data : (data?.title || data?.action || typeof data)
        );
        
        if (typeof data === 'object' && data !== null) {
            console.dir(data);
            if (Array.isArray(data)) {
                console.table(data);
            }
        } else {
            console[level](data);
        }

        console.trace('Execution Trace');
        console.groupEnd();
    } else {
        console.log(prefix, data);
    }

    // 2. On-Screen Overlay Rendering (for Mobile debugging without DevTools)
    const overlay = document.getElementById('debug-output');
    if (overlay) {
        const entry = document.createElement('pre');
        entry.style.cssText = `
            margin: 4px 0;
            padding: 8px;
            background: #1e1e1e;
            color: ${level === 'error' ? '#ff6b6b' : level === 'warn' ? '#fca5a5' : '#4ade80'};
            font-family: monospace;
            font-size: 11px;
            border-left: 3px solid ${level === 'error' ? '#ef4444' : '#22c55e'};
            white-space: pre-wrap;
            word-break: break-all;
        `;

        const formattedContent = typeof data === 'object' ? JSON.stringify(data, null, 2) : String(data);
        entry.textContent = `${prefix}\n${formattedContent}`;
        overlay.prepend(entry);
    }
};