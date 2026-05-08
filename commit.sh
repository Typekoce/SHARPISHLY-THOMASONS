#!/bin/bash

# ===================== commit.sh =====================
# Usage: ./commit.sh "Your commit message here"

clear

# Check if commit message was provided
if [ -z "$1" ]; then
    echo "❌ Error: No commit message provided!"
    echo "Usage: ./commit.sh \"Your commit message\""
    echo "Example: ./commit.sh \"feat: improve install.sh script\""
    exit 1
fi

COMMIT_MSG="$1"
LINE="--------------------------------------------------"

echo "$LINE"
echo "📌 Git Add"
echo "$LINE"
git add .
echo "✅ Staged all changes"

echo "$LINE"
echo "📌 Git Commit"
echo "$LINE"
git commit -m "$COMMIT_MSG"
echo "✅ Committed with message: \"$COMMIT_MSG\""

echo "$LINE"
echo "📌 Git Push"
echo "$LINE"
git push

echo "$LINE"
echo "🎉 All done!"