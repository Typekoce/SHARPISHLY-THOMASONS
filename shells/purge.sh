#!/bin/bash
echo "🛑 Stopping all SHARPISHLY related containers..."
# Stops any container starting with 'sharpishly'
docker ps -a | grep 'sharpishly' | awk '{print $1}' | xargs -r docker stop

echo "🗑️ Removing containers..."
docker ps -a | grep 'sharpishly' | awk '{print $1}' | xargs -r docker rm -f

echo "🌐 Cleaning orphaned networks..."
# This removes the bridge network that is likely causing the DNS failure
docker network prune -f

echo "💾 Removing stale volumes..."
docker volume prune -f

echo "✨ System is now clean."
docker ps -a
docker compose down -v --remove-orphans
docker network rm sharpishly-thomasons_sharpishly-net 2>/dev/null || true
rm -rf ./storage/*  # optional but recommended for clean test


# 2. Wipe the Docker network bridge specifically
docker network prune -f

# 3. Clear the Docker build cache (the 'hidden' culprit)
docker builder prune -f
