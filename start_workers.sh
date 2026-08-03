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

# Ensure log directory exists
mkdir -p "${LOG_DIR}"

# PID tracking for clean teardown
PIDS=()

cleanup() {
    echo ""
    echo "🛑 Stopping all neural and background workers..."
    
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
# 1. Start Ingestion / Vector Worker
# ---------------------------------------------------------------------
if [ ! -f "${INGESTION_WORKER}" ]; then
    echo "❌ Ingestion worker script missing: ${INGESTION_WORKER}"
    exit 1
fi

echo "→ Launching Ingestion RAG Worker..."
(
    cd "${ROOT_DIR}"
    exec "${PYTHON_BIN}" "${INGESTION_WORKER}"
) >> "${LOG_DIR}/ingestion_worker.log" 2>&1 &
PIDS+=($!)

# ---------------------------------------------------------------------
# 2. Command Queue / Supervisor Worker
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
            
            # Atomic move guard (skip if already in processing)
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

echo "=================================================="
echo "✅ All workers running in background."
echo "📋 Logs streaming to: ${LOG_DIR}/"
echo "Press [CTRL+C] to stop all processes."
echo "=================================================="

# Keep master process active and await termination signals
wait