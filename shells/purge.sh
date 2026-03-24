#!/usr/bin/env bash
set -euo pipefail

# Location: shells/purge.sh

echo "🛑 SHARPISHLY-THOMASONS Full Purge"
echo "==================================="

# Safety confirmation
read -p "⚠️  This will DELETE all containers, volumes, networks, and storage for this project. Continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 0
fi

echo "🛑 Stopping and removing all Sharpishly containers..."
docker compose down -v --remove-orphans --timeout 10 || true

# Extra safety: kill any remaining containers with "sharpishly" in name
echo "🗑️  Force-removing any leftover Sharpishly containers..."
docker ps -a --filter "name=sharpishly" -q | xargs -r docker rm -f

echo "🌐 Removing project-specific network..."
docker network rm sharpishly-thomasons_sharpishly-net 2>/dev/null || true

echo "💾 Removing project volumes..."
docker volume ls -q --filter "name=sharpishly-thomasons" | xargs -r docker volume rm -f

echo "🧹 Pruning dangling images, build cache, and unused networks..."
docker system prune -f --volumes

echo "🗑️  Clearing build cache (can be large)..."
docker builder prune -f --all

echo "🧼 Cleaning project directories..."
rm -rf ./storage/*
rm -rf ./storage/.gitkeep 2>/dev/null || true

# Optional: also clean common temp/cache dirs
rm -rf ./web/php/src/bootstrap/cache/* 2>/dev/null || true

echo "✅ Purge completed successfully!"
echo ""
echo "Current status:"
docker compose ps 2>/dev/null || echo "No compose services running."
echo ""
echo "Remaining Sharpishly-related containers:"
docker ps -a --filter "name=sharpishly" --format "table {{.Names}}\t{{.Status}}"

echo ""
echo "Next steps:"
echo "   1. Run: ./dev-up.sh"
echo "   2. Wait 15-20 seconds for MySQL to fully initialize"
echo "   3. Test: curl http://localhost:8080/php/scaffold/migrate"