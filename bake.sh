#!/usr/bin/env bash

# Exit immediately on error, unset variable, or pipe failure
set -euo pipefail

# Print help message
usage() {
    cat <<EOF
Usage: $0 <resource_name> [options]

Options:
  --table <name>   Custom database table name (default: derived from resource)
  --force          Overwrite existing backend and frontend files
  -h, --help       Show this help message

Examples:
  $0 timetable
  $0 todo --table tasks
  $0 timetable --force
EOF
    exit 1
}

# Parse Arguments
RESOURCE=""
TABLE_NAME=""
FORCE=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        -h|--help)
            usage
            ;;
        --table)
            TABLE_NAME="$2"
            shift 2
            ;;
        --force)
            FORCE=true
            shift
            ;;
        *)
            if [[ -z "$RESOURCE" ]]; then
                RESOURCE="$1"
                shift
            else
                echo "Error: Unexpected argument '$1'"
                usage
            fi
            ;;
    esac
done

if [[ -z "$RESOURCE" ]]; then
    echo "Error: Missing resource name."
    usage
fi

# Clean and transform casing using sed for multi-word / snake_case support
LOWER_NAME=$(echo "$RESOURCE" | tr '[:upper:]' '[:lower:]')
PASCAL_NAME=$(echo "$LOWER_NAME" | sed -r 's/(^|_)([a-z])/\U\2/g')
CAMEL_NAME=$(echo "$PASCAL_NAME" | sed -r 's/(^)([A-Z])/\L\2/')

# Fallback table name to LOWER_NAME if not explicitly overridden
TABLE_NAME="${TABLE_NAME:-$LOWER_NAME}"

# Resolve project directory structure
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${SCRIPT_DIR}"

# Backend Paths
APP_DIR="${PROJECT_ROOT}/web/php/src"
MODEL_DIR="${APP_DIR}/Models"
CONTROLLER_DIR="${APP_DIR}/Controllers"

MODEL_FILE="${MODEL_DIR}/${PASCAL_NAME}Model.php"
CONTROLLER_FILE="${CONTROLLER_DIR}/${PASCAL_NAME}Controller.php"

# Frontend Paths
FRONTEND_DIR="${PROJECT_ROOT}/web/frontend"
JS_DIR="${FRONTEND_DIR}/js"
VIEWS_DIR="${FRONTEND_DIR}/views/pages"

JS_SUBMENU_FILE="${JS_DIR}/${LOWER_NAME}_submenu_controller.js"
JS_CONTROLLER_FILE="${JS_DIR}/${LOWER_NAME}_controller.js"
HTML_VIEW_FILE="${VIEWS_DIR}/${LOWER_NAME}.html"

# Safeguard check to prevent overwriting existing files without --force
if [[ "$FORCE" = false ]]; then
    for FILE in "$MODEL_FILE" "$CONTROLLER_FILE" "$JS_SUBMENU_FILE" "$JS_CONTROLLER_FILE" "$HTML_VIEW_FILE"; do
        if [[ -f "$FILE" ]]; then
            echo "Error: File already exists at $FILE. Use --force to overwrite."
            exit 1
        fi
    done
fi

# Ensure output directories exist
mkdir -p "${MODEL_DIR}" "${CONTROLLER_DIR}" "${JS_DIR}" "${VIEWS_DIR}"

# ==============================================================================
# 1. Generate PHP Model
# ==============================================================================
cat <<PHP > "${MODEL_FILE}"
<?php

declare(strict_types=1);

namespace App\Models;

use Throwable;

class ${PASCAL_NAME}Model extends BaseModel
{
    public bool \$debug = true;

    public string \$tbl = '${TABLE_NAME}';

    public function create(array \$payload = []): array
    {
        if (\$this->debug === true && empty(\$payload)) {
            \$payload = [
                'title'       => '${PASCAL_NAME} Entry',
                'description' => 'Default ${TABLE_NAME} record generated via bake script',
                'source_id'   => 1,
                'status'      => 'active'
            ];
        }

        \$this->db->save(\$this->tbl, \$payload);
        return ['result' => \$this->read()];
    }

    public function update(string \$id = '', array \$payload = []): array
    {
        if (\$this->debug === true && empty(\$payload)) {
            \$payload = [
                'title'       => '${PASCAL_NAME} Entry - Updated',
                'description' => 'Updated ${TABLE_NAME} record',
                'source_id'   => 1,
                'status'      => 'completed'
            ];
        }

        if (!empty(\$id)) {
            \$existing = \$this->read(\$id);
            \$currentData = \$existing[0] ?? [];

            \$mergedPayload = array_merge(\$currentData, \$payload, ['id' => (int)\$id]);

            \$this->db->save(\$this->tbl, \$mergedPayload);
        }

        return ['result' => \$this->read(\$id)];
    }

    public function read(string \$id = ''): array
    {
        try {
            \$conditions = [
                'tbl'   => \$this->tbl,
                'order' => ['id' => 'DESC']
            ];

            if (!empty(\$id)) {
                \$conditions['where'] = ['id' => \$id];
            }

            return \$this->db->find(\$conditions);
        } catch (Throwable \$e) {
            \$logger = \$GLOBALS['logger'] ?? null;
            if (\$logger) {
                \$logger->error("${PASCAL_NAME}Model::read failed: " . \$e->getMessage());
            }

            return [
                'error'   => true,
                'message' => \$e->getMessage()
            ];
        }
    }

    public function delete(string \$id = ''): array
    {
        if (empty(\$id)) {
            return ['error' => true, 'message' => 'Missing ID for deletion'];
        }

        try {
            \$this->db->delete(\$this->tbl, ['id' => \$id]);
            return ['status' => 'deleted', 'id' => \$id];
        } catch (Throwable \$e) {
            return ['error' => true, 'message' => \$e->getMessage()];
        }
    }
}
PHP

# ==============================================================================
# 2. Generate PHP Controller
# ==============================================================================
cat <<PHP > "${CONTROLLER_FILE}"
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\\${PASCAL_NAME}Model;

class ${PASCAL_NAME}Controller extends BaseController 
{
    public function __construct()
    {
        parent::__construct();
        \$this->model = new ${PASCAL_NAME}Model();
    }    

    public function create(): void
    {
        \$payload = \$this->request();
        \$result  = \$this->model->create(\$payload);

        \$this->json([
            'status'   => 'success',
            'function' => __FUNCTION__,
            'class'    => __CLASS__,
            'method'   => __METHOD__,
            'response' => \$result
        ]);
    }

    public function read(string \$id = ''): void
    {
        \$result = \$this->model->read(\$id);
        \$this->json(['status' => 'success', 'data' => \$result]);
    }

    public function update(string \$id = ''): void
    {
        \$payload = \$this->request();
        \$result  = \$this->model->update(\$id, \$payload);
        \$this->json(['status' => 'success', 'data' => \$result]);
    }

    public function delete(string \$id = ''): void
    {
        \$result = \$this->model->delete(\$id);
        \$this->json(['status' => 'success', 'data' => \$result]);
    }
}
PHP

# ==============================================================================
# 3. Generate JS Submenu Controller
# ==============================================================================
cat <<JS > "${JS_SUBMENU_FILE}"
${PASCAL_NAME}Controller.submenu = function() {
    const menuItems = [
        {
            label: 'All Items',
            action: () => ${PASCAL_NAME}Controller.read()
        },
        {
            label: 'Pending Items',
            action: () => ${PASCAL_NAME}Controller.read('', { status: 'pending' })
        },
        {
            label: 'Completed Items',
            action: () => ${PASCAL_NAME}Controller.read('', { status: 'completed' })
        },
        {
            label: 'Refresh',
            class: 'btn btn-sm btn-outline-primary me-2',
            action: () => ${PASCAL_NAME}Controller.init()
        }
    ];

    App.dBug(menuItems);
    SubMenuController.menu(menuItems);
};
JS

# ==============================================================================
# 4. Generate JS Controller
# ==============================================================================
cat <<JS > "${JS_CONTROLLER_FILE}"
const ${PASCAL_NAME}Controller = {
    async init() {
        const recordsContainer = document.getElementById('${LOWER_NAME}-records');
        if (!recordsContainer) return;

        let form = document.getElementById('${LOWER_NAME}-form');
        if (!form) {
            form = App.item('form');
            form.id = '${LOWER_NAME}-form';
            form.className = 'mb-4';

            const schema = {
                'title': { id: '${LOWER_NAME}-title', placeholder: 'Title...' },
                'category': { id: '${LOWER_NAME}-category', placeholder: 'Category...' },
                'description': { id: '${LOWER_NAME}-desc', type: 'textarea', placeholder: 'Description...' }
            };
            App.eForm(form, schema);

            const submitBtn = App.item('button');
            submitBtn.type = 'submit';
            submitBtn.textContent = 'Add Entry';
            form.appendChild(submitBtn);

            form.onsubmit = (e) => this.create(e);

            recordsContainer.parentNode.insertBefore(form, recordsContainer);
        }

        this.submenu();
        await this.read();
    },

    async create(e) {
        e.preventDefault();
        const titleEl = document.getElementById('${LOWER_NAME}-title');
        const categoryEl = document.getElementById('${LOWER_NAME}-category');
        const descEl = document.getElementById('${LOWER_NAME}-desc');

        const payload = {
            title: titleEl?.value.trim(),
            category: categoryEl?.value.trim() || 'internal',
            description: descEl?.value.trim(),
            status: 'pending'
        };

        if (!payload.title) return;

        App.spinner();
        try {
            const res = await fetch(App.url('${LOWER_NAME}/create'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status === 'success') {
                document.getElementById('${LOWER_NAME}-form').reset();
                await this.read();
            }
        } finally {
            App.clearSpinner();
        }
    },

    async read(id = '', params = {}) {
        const recordsDiv = document.getElementById('${LOWER_NAME}-records');
        if (!recordsDiv) return;

        App.spinner();
        try {
            const endpoint = id ? \`${LOWER_NAME}/read/\${id}\` : '${LOWER_NAME}/read';
            const res = await fetch(App.url(endpoint));
            const result = await res.json();

            recordsDiv.textContent = '';

            let data = result.data || [];

            if (params.status) {
                data = data.filter(item => item.status === params.status);
            }

            if (result.status !== 'success' || data.length === 0) {
                const emptyMsg = App.item('p');
                emptyMsg.textContent = 'No records found.';
                recordsDiv.appendChild(emptyMsg);
                return;
            }

            const table = App.item('table');
            table.className = 'table';

            const thead = App.item('thead');
            const headerRow = App.item('tr');
            ['Title', 'Category', 'Status', 'Actions'].forEach(text => {
                const th = App.item('th');
                th.textContent = text;
                headerRow.appendChild(th);
            });
            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = App.item('tbody');
            data.forEach(item => {
                const tr = App.item('tr');

                const titleTd = App.item('td');
                const titleStrong = App.item('strong');
                titleStrong.textContent = item.title;
                titleTd.appendChild(titleStrong);

                if (item.description) {
                    titleTd.appendChild(App.item('br'));
                    const descSmall = App.item('small');
                    descSmall.textContent = item.description;
                    titleTd.appendChild(descSmall);
                }

                const categoryTd = App.item('td');
                categoryTd.textContent = item.category || 'internal';

                const statusTd = App.item('td');
                statusTd.textContent = item.status;

                const actionsTd = App.item('td');

                const toggleBtn = App.item('button');
                toggleBtn.textContent = item.status === 'completed' ? 'Mark Pending' : 'Mark Completed';
                toggleBtn.onclick = () => this.toggle(item.id, item.status);

                const deleteBtn = App.item('button');
                deleteBtn.textContent = 'Delete';
                deleteBtn.onclick = () => this.delete(item.id);

                actionsTd.appendChild(toggleBtn);
                actionsTd.appendChild(deleteBtn);

                tr.appendChild(titleTd);
                tr.appendChild(categoryTd);
                tr.appendChild(statusTd);
                tr.appendChild(actionsTd);

                tbody.appendChild(tr);
            });

            table.appendChild(tbody);
            recordsDiv.appendChild(table);
        } finally {
            App.clearSpinner();
        }
    },

    async toggle(id, currentStatus) {
        const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        App.spinner();
        try {
            const res = await fetch(App.url(\`${LOWER_NAME}/update/\${id}\`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const result = await res.json();
            if (result.status === 'success') await this.read();
        } finally {
            App.clearSpinner();
        }
    },

    async delete(id) {
        if (!confirm('Delete record?')) return;
        App.spinner();
        try {
            const res = await fetch(App.url(\`${LOWER_NAME}/delete/\${id}\`), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await res.json();
            if (result.status === 'success') await this.read();
        } finally {
            App.clearSpinner();
        }
    }
};
JS

# ==============================================================================
# 5. Generate HTML View File
# ==============================================================================
cat <<HTML > "${HTML_VIEW_FILE}"
<div id="${LOWER_NAME}">

<div id="submenu">
    <ul>
        <li>Create</li>
        <li>Read</li>
        <li>Update</li>
        <li>Delete</li>
    </ul>
</div>

<!-- records -->
<div id="${LOWER_NAME}-records"></div>

</div>

<!-- Debug Overlay Target -->
<div id="debug-output" style="position: fixed; bottom: 0; left: 0; right: 0; max-height: 40vh; overflow-y: auto; background: #111; padding: 8px; z-index: 9999; font-family: monospace; font-size: 11px; color: #4ade80;"></div>
HTML

# Permissions
chmod 755 "${MODEL_FILE}" "${CONTROLLER_FILE}" "${JS_SUBMENU_FILE}" "${JS_CONTROLLER_FILE}" "${HTML_VIEW_FILE}"

echo "Baked backend & frontend successfully:"
echo " - Model:                  ${MODEL_FILE}"
echo " - Controller:             ${CONTROLLER_FILE}"
echo " - JS Submenu Controller:  ${JS_SUBMENU_FILE}"
echo " - JS Controller:          ${JS_CONTROLLER_FILE}"
echo " - HTML View:              ${HTML_VIEW_FILE}"