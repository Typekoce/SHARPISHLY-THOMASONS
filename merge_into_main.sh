#!/usr/bin/env bash

# Stop immediately on errors, undefined variables, or pipe failures
set -euo pipefail

# Allow custom remote (defaults to 'origin')
REMOTE="${1:-origin}"

# Capture the currently checked-out branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Prevent running the script if already on main
if [ "$CURRENT_BRANCH" = "main" ]; then
    echo "Error: You are already on 'main'."
    exit 1
fi

# Ensure local 'main' branch exists
if ! git rev-parse --verify main >/dev/null 2>&1; then
    echo "Error: Local branch 'main' does not exist."
    exit 1
fi

# Ensure working directory is clean before switching branches
if [ -n "$(git status --porcelain)" ]; then
    echo "Error: You have uncommitted changes. Please commit or stash them first."
    exit 1
fi

# Ensure we always attempt to return to the original branch on exit/failure
trap 'echo "Returning to $CURRENT_BRANCH..."; git checkout "$CURRENT_BRANCH" 2>/dev/null || true' EXIT

echo "Updating main and merging '$CURRENT_BRANCH'..."

# Fetch latest remote references
git fetch "$REMOTE"

# Switch to main and sync with remote
git checkout main
git pull "$REMOTE" main

# Merge current feature branch into main with clear conflict handling
echo "Merging '$CURRENT_BRANCH' into 'main'..."
if ! git merge "$CURRENT_BRANCH"; then
    echo "Merge failed (likely conflicts). You are back on '$CURRENT_BRANCH'."
    echo "Resolve conflicts, then re-run this script or complete the merge manually."
    exit 1
fi

# Optional prompt before pushing to remote main
read -rp "Push merged 'main' to $REMOTE? [y/N] " confirm
if [[ "$confirm" =~ ^[Yy]$ ]]; then
    git push "$REMOTE" main
    echo "Successfully merged '$CURRENT_BRANCH' into 'main' and pushed to $REMOTE."
else
    echo "Aborting push. You are currently on 'main' locally; push manually when ready."
    # Clear trap to prevent automatic return to feature branch so you can inspect 'main'
    trap - EXIT
    exit 0
fi