/**
 * Global dBug Renderer
 * Converts a JS variable recursively into styled, collapsible HTML tables.
 */
App.dBug = function(data, typeName = null) {
    if (data === null || data === undefined) {
        const cell = document.createElement('span');
        cell.style.fontWeight = 'bold';
        cell.style.fontStyle = 'italic';
        cell.textContent = 'NULL';
        return cell;
    }

    if (typeof data === 'boolean') {
        const cell = document.createElement('span');
        cell.style.fontWeight = 'bold';
        cell.style.fontStyle = 'italic';
        cell.textContent = data ? 'TRUE' : 'FALSE';
        return cell;
    }

    if (typeof data !== 'object') {
        const cell = document.createElement('span');
        cell.style.fontFamily = 'Verdana, Arial, Helvetica, sans-serif';
        cell.style.fontSize = '12px';
        cell.textContent = String(data).trim() === '' ? '[empty string]' : String(data);
        return cell;
    }

    const isArr = Array.isArray(data);
    const type = typeName || (isArr ? 'array' : 'object');

    const isArrayType = type === 'array';
    const theme = {
        tableBg: isArrayType ? '#006600' : '#0000CC',
        borderColor: isArrayType ? 'green' : 'blue',
        headerBg: isArrayType ? '#009900' : '#4444CC',
        keyBg: isArrayType ? '#CCFFCC' : '#CCDDFF'
    };

    const table = document.createElement('table');
    table.className = `dBug_${type}`;
    table.style.fontFamily = 'Verdana, Arial, Helvetica, sans-serif';
    table.style.color = '#000000';
    table.style.fontSize = '12px';
    table.style.backgroundColor = theme.tableBg;
    table.style.border = `1px solid ${theme.borderColor}`;
    table.style.borderCollapse = 'separate';
    table.style.borderSpacing = '2px';
    table.style.margin = '4px auto';
    table.style.width = '90%';

    // Header Row
    const headerTr = document.createElement('tr');
    const headerTd = document.createElement('td');
    headerTd.className = `dBug_${type}Header`;
    headerTd.setAttribute('colspan', '2');
    headerTd.textContent = type;
    headerTd.style.backgroundColor = theme.headerBg;
    headerTd.style.border = `1px solid ${theme.borderColor}`;
    headerTd.style.color = '#FFFFFF';
    headerTd.style.fontWeight = 'bold';
    headerTd.style.cursor = 'pointer';
    headerTd.style.padding = '3px';

    headerTd.addEventListener('click', () => {
        const rows = table.querySelectorAll(':scope > tr:not(:first-child)');
        const isHidden = Array.from(rows).some(r => r.style.display === 'none');
        rows.forEach(r => r.style.display = isHidden ? '' : 'none');
        headerTd.style.fontStyle = isHidden ? 'normal' : 'italic';
    });

    headerTr.appendChild(headerTd);
    table.appendChild(headerTr);

    // Body Rows
    const entries = Object.entries(data);
    if (entries.length === 0) {
        const emptyTr = document.createElement('tr');
        const emptyTd = document.createElement('td');
        emptyTd.setAttribute('colspan', '2');
        emptyTd.style.backgroundColor = '#FFFFFF';
        emptyTd.style.border = `1px solid ${theme.borderColor}`;
        emptyTd.style.padding = '3px';
        emptyTd.textContent = '[empty]';
        emptyTr.appendChild(emptyTd);
        table.appendChild(emptyTr);
        return table;
    }

    entries.forEach(([key, val]) => {
        const tr = document.createElement('tr');

        const keyTd = document.createElement('td');
        keyTd.className = `dBug_${type}Key`;
        keyTd.textContent = key;
        keyTd.style.backgroundColor = theme.keyBg;
        keyTd.style.border = `1px solid ${theme.borderColor}`;
        keyTd.style.cursor = 'pointer';
        keyTd.style.padding = '3px';
        keyTd.style.verticalAlign = 'top';

        keyTd.addEventListener('click', () => {
            const valTd = tr.children[1];
            if (valTd) {
                valTd.style.display = valTd.style.display === 'none' ? '' : 'none';
            }
        });

        const valTd = document.createElement('td');
        valTd.style.backgroundColor = '#FFFFFF';
        valTd.style.border = `1px solid ${theme.borderColor}`;
        valTd.style.padding = '3px';
        
        // Recursive call to App.dBug
        valTd.appendChild(App.dBug(val));

        tr.append(keyTd, valTd);
        table.appendChild(tr);
    });

    return table;
};