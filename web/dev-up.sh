#!/usr/bin/env bash
set -euo pipefail

# ────────────────────────────────────────────────
# dev-up.sh (Minimalist Version)
# Purpose: Tear down, rebuild, and tail logs
# ────────────────────────────────────────────────

echo "─── Resetting Environment ───"

# 1. Kill everything (Orphans + Volumes + Stopped Containers)
# --volumes ensures the database starts fresh if you've changed schemas
docker compose down --remove-orphans --volumes --timeout 5

# 2. Rebuild and Start
# --build ensures any changes to your Dockerfile/configs are baked in
docker compose up -d --build

# 3. Handover to Logs
echo "─── Services Up. Tailing PHP logs... ───"
#docker compose logs -f php
