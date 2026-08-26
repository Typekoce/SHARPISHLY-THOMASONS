#!/usr/bin/env bash

# Exit immediately on error, unset variable, or pipe failure
set -euo pipefail

# Print help message
usage() {
    cat <<EOF
Usage: $0 <resource_name> [options]

Options:
  --table <name>   Custom database table name (default: derived from resource)
  --force          Overwrite existing Model and Controller files
  -h, --help       Show this help message

Examples:
  $0 timetable
  $0 user_profile --table account_profiles
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

# Resolve project directory structure (bake.sh lives in project root)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${SCRIPT_DIR}"

APP_DIR="${PROJECT_ROOT}/web/php/src"
MODEL_DIR="${APP_DIR}/Models"
CONTROLLER_DIR="${APP_DIR}/Controllers"

MODEL_FILE="${MODEL_DIR}/${PASCAL_NAME}Model.php"
CONTROLLER_FILE="${CONTROLLER_DIR}/${PASCAL_NAME}Controller.php"

# Safeguard check to prevent overwriting existing files without --force
if [[ "$FORCE" = false ]]; then
    if [[ -f "$MODEL_FILE" ]]; then
        echo "Error: Model already exists at $MODEL_FILE. Use --force to overwrite."
        exit 1
    fi
    if [[ -f "$CONTROLLER_FILE" ]]; then
        echo "Error: Controller already exists at $CONTROLLER_FILE. Use --force to overwrite."
        exit 1
    fi
fi

# Ensure output directories exist
mkdir -p "${MODEL_DIR}"
mkdir -p "${CONTROLLER_DIR}"

# 1. Generate Model Class File
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

# 2. Generate Controller Class File
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

chmod 755 "${MODEL_FILE}" "${CONTROLLER_FILE}"

echo "Baked successfully:"
echo " - Model:      ${MODEL_FILE}"
echo " - Controller: ${CONTROLLER_FILE}"
