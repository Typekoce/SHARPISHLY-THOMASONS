#!/bin/bash
# Location: dev-up.sh

echo "🚀 Resetting Sharpishly Organism..."

# Wipe the old state
docker compose down

# Force a rebuild of the PHP container to bake in the Redis extension
echo "📦 Rebuilding PHP with Redis CNS support..."
docker compose build --no-cache php

# Bring up the corrected organism
docker compose up -d

echo "✅ System is rising. Use 'docker compose ps' to check health."