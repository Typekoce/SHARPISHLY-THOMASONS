#!/usr/bin/env bash

# Exit immediately if standard execution fails, protect against unbound vars
set -euo pipefail

# Enable nullglob so empty globs expand to nothing instead of literal strings
shopt -s nullglob

# Environment & Directory Setup
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="${ROOT_DIR}/storage/logs"
PYTHON_BIN="${ROOT_DIR}/venv/bin/python3"
INGESTION_WORKER="${ROOT_DIR}/pymvc/app/ingestion_worker.py"
RAG_SERVICE="${ROOT_DIR}/pymvc/app/rag_service.py"

# Ensure log directory exists
mkdir -p "${LOG_DIR}"

# PID tracking for clean teardown
PIDS=()

cleanup() {
    echo ""
    echo "🛑 Stopping all neural, API, and background workers..."
    
    # Stage 1: Graceful SIGTERM
    for pid in "${PIDS[@]}"; do
        if kill -0 "$pid" 2>/dev/null; then
            kill -TERM "$pid" 2>/dev/null || true
        fi
    done
    
    sleep 1
    
    # Stage 2: Forceful SIGKILL for lingering processes
    for pid in "${PIDS[@]}"; do
        if kill -0 "$pid" 2>/dev/null; then
            kill -KILL "$pid" 2>/dev/null || true
        fi
    done

    echo "✅ All workers terminated cleanly."
    exit 0
}

# Trap signals to ensure graceful shutdown
trap cleanup SIGINT SIGTERM EXIT

echo "=================================================="
echo "🚀 Starting Consolidated Sharpishly Worker Suite"
echo "=================================================="

# Check Python environment
if [ ! -f "${PYTHON_BIN}" ]; then
    PYTHON_BIN="/usr/bin/python3"
    echo "⚠️  venv not found at ./venv. Falling back to system python: ${PYTHON_BIN}"
fi

export PYTHONPATH="${ROOT_DIR}/pymvc:${PYTHONPATH:-}"

# ---------------------------------------------------------------------
# 1. Ollama Neural Engine & Model Service
# ---------------------------------------------------------------------
if command -v ollama >/dev/null 2>&1; then
    if curl -s http://127.0.0.1:11434/api/tags >/dev/null 2>&1; then
        echo "→ Ollama Neural Engine is already active on port 11434."
    else
        echo "→ Launching Ollama Neural Engine..."
        ollama serve >> "${LOG_DIR}/ollama.log" 2>&1 &
        PIDS+=($!)
        sleep 2
    fi
else
    echo "⚠️  Ollama binary not found in PATH. Skipping local model engine startup."
fi

# ---------------------------------------------------------------------
# 2. Local RAG Service Endpoint
# ---------------------------------------------------------------------
if [ -f "${RAG_SERVICE}" ]; then
    echo "→ Launching RAG API Service..."
    (
        cd "${ROOT_DIR}"
        exec "${PYTHON_BIN}" "${RAG_SERVICE}"
    ) >> "${LOG_DIR}/rag_service.log" 2>&1 &
    PIDS+=($!)
else
    echo "⚠️  RAG service script not found at ${RAG_SERVICE}. Skipping."
fi

# ---------------------------------------------------------------------
# 3. Ingestion / Vector Worker
# ---------------------------------------------------------------------
if [ -f "${INGESTION_WORKER}" ]; then
    echo "→ Launching Ingestion RAG Worker..."
    (
        cd "${ROOT_DIR}"
        exec "${PYTHON_BIN}" "${INGESTION_WORKER}"
    ) >> "${LOG_DIR}/ingestion_worker.log" 2>&1 &
    PIDS+=($!)
else
    echo "❌ Ingestion worker script missing: ${INGESTION_WORKER}"
    exit 1
fi

# ---------------------------------------------------------------------
# 4. Command Queue / Supervisor Worker
# ---------------------------------------------------------------------
echo "→ Launching Command Queue / Supervisor Worker..."
(
    QUEUE_DIR="${ROOT_DIR}/storage/cmd/jobs/waiting"
    PROC_DIR="${ROOT_DIR}/storage/cmd/jobs/processing"
    DONE_DIR="${ROOT_DIR}/storage/cmd/jobs/completed"
    
    mkdir -p "${QUEUE_DIR}" "${PROC_DIR}" "${DONE_DIR}"

    while true; do
        for job in "${QUEUE_DIR}"/*.sh; do
            [ -e "${job}" ] || continue
            
            jobname=$(basename "${job}")
            target="${PROC_DIR}/${jobname}"
            
            # Atomic move guard
            if ! mv -n "${job}" "${target}" 2>/dev/null; then
                continue
            fi
            
            logname="${jobname}.log"
            
            # Execute job script and capture execution output
            bash "${target}" > "${PROC_DIR}/${logname}" 2>&1 || true
            
            # Archive job script and output log
            mv "${target}" "${DONE_DIR}/" 2>/dev/null || true
            mv "${PROC_DIR}/${logname}" "${DONE_DIR}/" 2>/dev/null || true
        done
        sleep 1
    done
) >> "${LOG_DIR}/cmd_worker.log" 2>&1 &
PIDS+=($!)

# ---------------------------------------------------------------------
# 5. Agent Execute Worker Loop
# ---------------------------------------------------------------------
echo "→ Launching Agent Execution Trigger..."
(
    while true; do
        # Example: Triggering execution via local HTTP route for pending jobs
        curl -s -X POST "http://127.0.0.1/php/agent-execute/start" \
             -H "Content-Type: application/json" \
             -d '{}' >/dev/null 2>&1 || true
        sleep 2
    done
) >> "${LOG_DIR}/agent_execute.log" 2>&1 &
PIDS+=($!)

echo "=================================================="
echo "✅ All workers & neural services running."
echo "📋 Logs streaming to: ${LOG_DIR}/"
echo "Press [CTRL+C] to stop all processes."
echo "=================================================="

# Keep master process active and await termination signals
wait