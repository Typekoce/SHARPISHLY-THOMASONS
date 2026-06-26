#!/bin/bash
# clean_history.sh
# Safely initiates an interactive rebase to allow manual removal of the poisoned commit.

set -e

BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "--- Starting cleanup for: $BRANCH ---"
echo "Locating the poisoned commit in the history..."

# Find the parent of the commit that contains the secret (2a4a013)
# We rebase from the commit immediately preceding it.
git rebase -i 2a4a013^

echo "--- Rebase session closed ---"
echo "1. Verify the secret is gone: git log --oneline -n 5"
echo "2. Verify the file is ignored: git check-ignore -v client_secret.json"
echo "3. If clean, push with: git push origin $BRANCH --force"