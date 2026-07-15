#!/bin/bash

# Define the base directory
BASE_DIR="$HOME/project"

# Function to start a worker in a named tmux session
# Usage: start_worker <session_name> <command>
start_worker() {
    local session=$1
    local cmd=$2
    
    # Check if session exists; if not, create it detached
    if ! tmux has-session -t "$session" 2>/dev/null; then
        tmux new-session -d -s "$session" -c "$BASE_DIR"
        tmux send-keys -t "$session" "$cmd" C-m
        echo "Started session: $session"
    else
        echo "Session $session is already running."
    fi
}

# Start specific workers
start_worker "telephony_worker" "php bin/worker.php --type=telephony"
start_worker "archivist_worker" "php bin/worker.php --type=archivist"
start_worker "himalaya_sync"    "himalaya sync"