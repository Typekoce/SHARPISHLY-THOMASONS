#!/bin/bash

# clear terminal
clear

# SHARPISHLY-THOMASONS: PyMVC Scaffolder
# Usage: ./scaffold_mvc.sh [component1 [component2 ...]]

CONTROLLER_DIR="app/controllers"
MODEL_DIR="app/models"

# Ensure directories exist
mkdir -p "$CONTROLLER_DIR" "$MODEL_DIR" "storage/uploads" "storage/results" "storage/logs"

echo "🏗️  Scaffolding PyMVC Controllers and Models..."

# Function to capitalize first letter
capitalize() {
    printf '%s' "${1^}"
}

# Function to create Controller/Model pairs
create_component() {
    local name="$1"
    local method="$2"
    local message="$3"
    local controller_file="$CONTROLLER_DIR/${name}_controller.py"
    local model_file="$MODEL_DIR/${name}_model.py"
    local ClassName=$(capitalize "$name")

    # Controller
    if [[ ! -f "$controller_file" ]]; then
        cat > "$controller_file" <<EOF
from app.models.${name}_model import ${ClassName}


class ${ClassName}Controller:
    @staticmethod
    def ${method}():
        model = ${ClassName}()
        result = model.execute()
        if result:
            return "200 - OK: ${message}"
        return "500 - Error: ${ClassName} operation failed."
EOF
        echo "✅ Created $controller_file"
    else
        echo "⏭️  $controller_file exists"
    fi

    # Model
    if [[ ! -f "$model_file" ]]; then
        cat > "$model_file" <<EOF
class ${ClassName}:
    def execute(self):
        # Placeholder for future NP logic
        print(f"${ClassName} executed successfully")
        return True
EOF
        echo "✅ Created $model_file"
    else
        echo "⏭️  $model_file exists"
    fi
}

# Get components from args (or prompt)
if [[ $# -eq 0 ]]; then
    echo "No components specified. Using defaults or enter names (space-separated):"
    read -r -a COMPONENTS
    [[ ${#COMPONENTS[@]} -eq 0 ]] && COMPONENTS=("upload" "job" "health")
else
    COMPONENTS=("$@")
fi

echo "📋 Creating: ${COMPONENTS[*]}"

# Component mappings: name -> (method message)
declare -A COMPONENTS_MAP=(
    ["users"]="create_user|User created successfully."
    ["jobs"]="get_status|Job status retrieved."
    ["upload"]="handle_ingestion|File received and staged for NP."
    ["cleanup"]="purge_artifacts|Temporary storage cleared."
    ["log"]="record_event|System event logged."
    ["health"]="check_system|All systems green. Models operational."
)

for comp in "${COMPONENTS[@]}"; do
    if [[ -n "${COMPONENTS_MAP[$comp]+isset}" ]]; then
        IFS='|' read -r method message <<< "${COMPONENTS_MAP[$comp]}"
        create_component "$comp" "$method" "$message"
    else
        create_component "$comp" "handle_${comp}" "${comp^} operation completed."
    fi
done

echo "🚀 Complete! Files:"
find app/controllers app/models -name "*_controller.py" -o -name "*_model.py" | head -10