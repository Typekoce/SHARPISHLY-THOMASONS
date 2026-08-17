#!/bin/bash
set -euo pipefail

# 1. Validate argument first before touching files
if [ -z "${1:-}" ]; then
    echo "❌ Error: No commit message provided!"
    exit 1
fi

ignore="$HOME/Documents/SHARPISHLY-IGNORE"
home="$HOME/Documents/SHARPISHLY-THOMASONS"
line="----------------------------"
target_file="${TARGET_FILE:-env.php}"

# Ensure we operate from the repository root
cd "$home" || exit 1

mkdir -p "$ignore"

# 2. Guarantee file restoration even if git commands fail
cleanup() {
    if [ -f "$ignore/$target_file" ]; then
        echo "$line"
        echo "Moving $target_file to $home"
        mv "$ignore/$target_file" "$home/"
    fi
}
trap cleanup EXIT

# 3. Quarantine target file if present
if [ -f "$target_file" ]; then
    echo "$line"
    echo "Moving $target_file to ignore folder"
    mv "$target_file" "$ignore/"
fi

#
git status

# 4. Stage changes
git add .

# 5. Commit only if staged changes exist to avoid set -e failure
if ! git diff --cached --quiet; then
    git commit -m "$1"
    git push
else
    echo "⚠️  No changes to commit."
fi