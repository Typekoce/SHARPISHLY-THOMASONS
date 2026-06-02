#!/bin/bash
# agents_workflow.sh - Comprehensive Agent Workflow & State Infrastructure
BASE_DIR="$HOME/Documents/SHARPISHLY-THOMASONS"
# We unify the root under storage/tasks to align with the new orchestration plan
TASK_ROOT="$BASE_DIR/storage/tasks"

echo "🚀 Setting up Comprehensive Agent Infrastructure..."

# 1. State-Machine Directories (The new Orchestration Backbone)
# We include the previous 'agents' paths within this structure for backward compatibility
STATES=("pending" "in-progress" "completed" "failed")
TYPES=("email" "browser" "neural")

for state in "${STATES[@]}"; do
    for type in "${TYPES[@]}"; do
        mkdir -p "$TASK_ROOT/$state/$type"
        echo "→ Created: $TASK_ROOT/$state/$type"
    done
done

# 2. Legacy/Specific Path Links (Ensuring your existing worker continues to function)
# Linking legacy 'waiting' to 'pending' to keep your current worker logic happy
mkdir -p "$BASE_DIR/storage/agents/emails/waiting"
ln -sf "$TASK_ROOT/pending/email" "$BASE_DIR/storage/agents/emails/waiting"

# 3. Permissions
chmod -R 755 "$TASK_ROOT"
chmod -R 755 "$BASE_DIR/storage/agents"

echo "✅ Agent infrastructure fully initialized."
