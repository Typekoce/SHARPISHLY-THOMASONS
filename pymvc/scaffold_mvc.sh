#!/bin/bash

# SHARPISHLY-THOMASONS: PyMVC Scaffolder
# Run this from the root of the pymvc directory

CONTROLLER_DIR="app/controllers"
MODEL_DIR="app/models"

# Ensure directories exist
mkdir -p "$CONTROLLER_DIR"
mkdir -p "$MODEL_DIR"
mkdir -p "storage/uploads" "storage/results" "storage/logs"

echo "🏗️  Scaffolding PyMVC Controllers and Models..."

# Function to create Controller/Model pairs
create_component() {
    local name=$1
    local method=$2
    local message=$3

    local controller_file="$CONTROLLER_DIR/${name}_controller.py"
    local model_file="$MODEL_DIR/${name}_model.py"

    # Create Controller if it doesn't exist
    if [ ! -f "$controller_file" ]; then
        cat <<EOF > "$controller_file"
from app.models.${name}_model import ${name^}Model

class ${name^}Controller:
    @staticmethod
    def ${method}():
        model = ${name^}Model()
        result = model.execute()
        if result:
            return "200 - OK: ${message}"
        return "500 - Error: ${name^} operation failed."
EOF
        echo "✅ Created $controller_file"
    fi

    # Create Model if it doesn't exist
    if [ ! -f "$model_file" ]; then
        cat <<EOF > "$model_file"
class ${name^}Model:
    def execute(self):
        # Placeholder for future NP logic
        return True
EOF
        echo "✅ Created $model_file"
    fi
}

# Define components: name, method, success_message
create_component "upload" "handle_ingestion" "File received and staged for NP."
create_component "job" "get_status" "Job status retrieved."
create_component "cleanup" "purge_artifacts" "Temporary storage cleared."
create_component "log" "record_event" "System event logged."
create_component "health" "check_system" "All systems green. Models operational."

echo "🚀 Scaffolding complete. Update app/routes.py to map the new paths."
