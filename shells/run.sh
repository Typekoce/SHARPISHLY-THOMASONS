#!/bin/bash

# SHARPISHLY-THOMASONS Unified Task Runner
# Usage: ./run.sh [command]

case "$1" in
  "up")
    echo "🚀 Starting Environment..."
    docker compose up -d --build
    ;;

  "down")
    echo "🛑 Stopping Environment..."
    docker compose down
    ;;

  "test")
    echo "🧪 Running Cross-Platform Unit Tests..."
    echo "--- PHP Suite ---"
    docker exec sharpishly-php vendor/bin/phpunit tests/php
    echo "--- Python Suite ---"
    docker exec sharpishly-ai-api pytest tests/ai
    ;;

  "logs")
    echo "📋 Tailings Logs (Press Ctrl+C to stop)..."
    docker compose logs -f
    ;;

  "clean")
    echo "🧹 Deep Cleaning: Removing volumes and orphans..."
    docker compose down -v --remove-orphans
    ;;

  "php-shell")
    docker exec -it sharpishly-php sh
    ;;

  "ai-shell")
    docker exec -it sharpishly-ai-api sh
    ;;

  *)
    echo "Usage: ./run.sh {up|down|test|logs|clean|php-shell|ai-shell}"
    exit 1
    ;;
esac