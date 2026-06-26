#!/bin/bash
# Simplified commit.sh (The hook now handles the quarantine)

if [ -z "$1" ]; then
    echo "❌ Error: No commit message provided!"
    exit 1
fi

git add .
git commit -m "$1"
git push