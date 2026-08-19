#!/usr/bin/env bash

set -e

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" || "${1:-}" == "-n" ]]; then
    DRY_RUN=1
    echo "=== DRY RUN MODE (No commits will be made) ==="
fi

# Early exit if nothing to commit
if [ -z "$(git status --porcelain)" ]; then
    echo "No changes to commit."
    exit 0
fi

# Map target directories directly to their commit scopes
declare -A TARGETS=(
    ["web/frontend"]="frontend"
    ["web/php/src/Controllers"]="controllers"
    ["web/php/src/Models"]="models"
    ["web/php/src/Services"]="services"
    ["web/php/src/Security"]="security"
    ["web/php/src"]="php-core"
    ["web/php"]="web-php"
    ["web"]="web"
    ["pymvc"]="pymvc"
    ["pymvc-v1"]="pymvc-v1"
    ["llm"]="llm"
    ["infra"]="infra"
    ["installation"]="installation"
    ["diagnostics"]="diagnostics"
    ["system"]="system"
    ["docs"]="docs"
    ["tests"]="tests"
    [".config"]="config"
    [".github"]="ci"
)

# Explicit execution order to stage specific subdirectories before parent paths
ORDER=(
    "web/frontend"
    "web/php/src/Controllers"
    "web/php/src/Models"
    "web/php/src/Services"
    "web/php/src/Security"
    "web/php/src"
    "web/php"
    "web"
    "pymvc"
    "pymvc-v1"
    "llm"
    "infra"
    "installation"
    "diagnostics"
    "system"
    "docs"
    "tests"
    ".config"
    ".github"
)

run_git() {
    if [ "$DRY_RUN" -eq 1 ]; then
        echo "  [dry-run] git $*"
    else
        git "$@"
    fi
}

# Loop through each mapped directory and commit changes atomically
for dir in "${ORDER[@]}"; do
    scope="${TARGETS[$dir]}"
    
    if [ -d "$dir" ] && [ -n "$(git status --porcelain "$dir")" ]; then
        echo "--> [$scope] Committing changes in $dir..."
        run_git add "$dir"
        run_git commit -m "chore($scope): update $scope files"
    fi
done

# Catch-all for remaining top-level root files, explicitly ignoring storage
if [ -n "$(git status --porcelain -- ':!storage')" ]; then
    echo "--> [root] Committing remaining project root files (excluding storage/)..."
    run_git add . ':!storage'
    run_git commit -m "chore(root): update project configuration and root files"
fi

echo "Done! All project directories processed."