#!/usr/bin/env bash
set -euo pipefail

line'-----------'

# clear terminal
clear

echo "─── Resetting Thomasons V3 Stack ───"

# 1. Clean shutdown (volumes included for fresh DB state in dev)
docker compose down -v --remove-orphans --volumes --timeout 8 > /dev/null 2>&1 || true

# 1.1 Make directories and files for logging
echo $line"Make storage/log/app.log"

sudo mkdir -p storage/log/

sudo  touch storage/log/app.log
sudo  touch storage/log/nginx_access.log
sudo  touch storage/log/nginx_error


sudo chmod 777 -R storage/


# 2. Rebuild & start
docker compose up -d --build --force-recreate



sleep 4  # give php/db a moment to become responsive

# Inside your root dev-up.sh
echo "🚀 Validating Database Schema..."

# Trigger the migration via the Nginx entry point
# MIGRATE_RESPONSE=$(curl -s "http://localhost:8080/php/scaffold/migrate")

# if [[ $MIGRATE_RESPONSE == *"Applied"* ]]; then
#     echo "✅ Database Migrated Successfully."
# elif [[ $MIGRATE_RESPONSE == *"up to date"* ]]; then
#     echo "📋 Schema is already current."
# else
#     echo "❌ Migration Failed. Check docker logs -f php."
#     echo "Response: $MIGRATE_RESPONSE"
# fi

echo "─── Stack is Live ───"
echo "→ UI:   http://localhost:8080"
echo "→ API:  http://localhost:8080/php"
echo ""
echo "SUGGESTED TABS / COMMANDS:"
echo "  Tab 1:  docker compose logs -f nginx       (web server)"
echo "  Tab 2:  docker compose logs -f php         (web requests)"
echo "  Tab 3:  docker compose logs -f php-worker  (background queue)"
echo ""
echo "Quick test:"
echo "  curl -i http://localhost:8080/php"
echo "──────────────────────"
