#!/bin/bash
clear
# 1. Get current branch name
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "--- Committing to $CURRENT_BRANCH only ---"

# 2. Add all changes
git add .

# 3. Capture message
if [ -z "$1" ]
  then
    read -p "Enter commit message: " msg
  else
    msg="$1"
fi

# 4. Commit and Push
git commit -m "$msg"
git push origin $CURRENT_BRANCH

echo "------------------------------------------"
echo "Done! Changes pushed to $CURRENT_BRANCH."
echo "Main branch was NOT affected."
