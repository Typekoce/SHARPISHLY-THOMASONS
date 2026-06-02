#!/bin/bash
# Launch the email worker using the venv's python executable
cd "$(dirname "$0")"

# Path to the venv python interpreter
VENV_PYTHON="../venv/bin/python"

# Launch the worker
nohup "$VENV_PYTHON" app/email_worker.py > ../storage/logs/email_worker.log 2>&1 &

echo "Email worker started using virtual environment."
