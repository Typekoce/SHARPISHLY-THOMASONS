#!/usr/bin/env bash
set -euo pipefail

line='------------------------------------------------'

# Clear terminal for a clean start
clear

echo "─── Resetting Thomasons V3 Stack ───"

# 1. Clean shutdown (volumes included for fresh DB state in dev)
# This ensures no "ghost" schemas haunt your new migrations.
echo "🛑 Stopping existing containers and purging volumes..."
docker compose down -v --remove-orphans --volumes --timeout 8 > /dev/null 2>&1 || true

# 1.1 Make directories and files for logging
echo "$line"
echo "📂 Initializing storage and log files..."

# Create necessary directories
sudo mkdir -p storage/log/
sudo mkdir -p storage/uploads/
sudo mkdir -p database/neo4j/data/

# Create log files with correct extensions for Nginx/PHP compatibility
sudo touch storage/log/app.log
sudo touch storage/log/nginx_access.log
sudo touch storage/log/nginx_error.log

# Ensure entrypoints and scripts are executable
if [ -f "ai/entrypoint.sh" ]; then
    sudo chmod +x ai/entrypoint.sh
fi
if [ -f "migrate.sh" ]; then
    sudo chmod +x migrate.sh
fi

# Set broad permissions for local development shared volumes
echo "🔐 Setting permissions for shared storage..."
sudo chmod -R 777 storage/
sudo chmod -R 777 database/

# 2. Rebuild & start
echo "$line"
echo "🏗️  Rebuilding and launching services..."
docker compose up -d --build --force-recreate

# Wait for essential services to settle
echo "⏳ Waiting for MariaDB and PHP to become responsive..."
sleep 10 

# 3. Database Schema Synchronization
echo "$line"
echo "🚀 Validating Database Schema via Nginx..."

# Trigger the migration via the Nginx entry point
# Using -f to fail silently on server errors, -s for silent mode
MIGRATE_RESPONSE=$(curl -s -f "http://localhost:8080/php/scaffold/migrate" || echo "FAILED")

if [[ "$MIGRATE_RESPONSE" == *"success"* ]]; then
    echo "✅ Database Migrated Successfully."
elif [[ "$MIGRATE_RESPONSE" == "FAILED" ]]; then
    echo "❌ Migration Failed. Check 'docker compose logs php' for details."
else
    echo "⚠️  Unexpected Migration Response: $MIGRATE_RESPONSE"
fi

echo "$line"
echo "─── Stack is Live ───"
echo "→ UI:        http://localhost:8080"
echo "→ PHP API:   http://localhost:8080/php"
echo "→ AI API:    http://localhost:8000"
echo "→ NEO4J:     http://localhost:7474"
echo ""
echo "SUGGESTED DEBUG COMMANDS:"
echo "  1. View PHP Requests:   docker compose logs -f php"
echo "  2. View AI Worker:      docker compose logs -f ai"
echo "  3. View DB Status:      docker compose exec db mysql -u root -p"
echo "────────────────────────────────────────────────"