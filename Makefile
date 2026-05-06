# --- Variables ---
PROJECT_PATH := $(shell pwd)
VENV := venv
PYTHON := $(PROJECT_PATH)/$(VENV)/bin/python3
PIP := $(PROJECT_PATH)/$(VENV)/bin/pip
DB_NAME := sharpishly
DB_USER := sharpadmin
DB_PASS := sharpish_pass_2026

# --- Primary Commands ---
.PHONY: help all fix-permissions setup-sys setup-db setup-db-migration setup-web setup-python run-worker logs setup-test-job

help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

all: setup-sys setup-db setup-web setup-python fix-permissions ## Execute full provisioning flow

fix-permissions: ## 🔐 Apply permanent SetGID & Group permissions
	@echo "🛠️  Applying SetGID (2775) to storage..."
	@sudo chown -R vboxuser:www-data storage
	@sudo chmod -R 2775 storage
	@find storage -type d -exec sudo chmod g+s {} +
	@echo "✅ Permissions grounded."

setup-sys: ## [1/5] Install LEMP stack, Python, and MariaDB
	@sudo apt update && sudo apt install -y nginx mariadb-server php-fpm php-mysql python3-venv python3-pip curl
	@sudo systemctl enable nginx
	@sudo systemctl enable mariadb

setup-db: ## [2/5] Initialize MariaDB database and user
	@echo "🚀 Initializing MariaDB..."
	@sudo mariadb -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	@sudo mariadb -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'localhost' IDENTIFIED BY '$(DB_PASS)';"
	@sudo mariadb -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'localhost';"
	@sudo mariadb -e "FLUSH PRIVILEGES;"
	@$(MAKE) setup-db-migration

setup-db-migration: ## [2.5/5] Run PHP database migrations
	@echo "🗄️  Running Database Migrations..."
	@curl -i http://sharpishly.dev/php/scaffold/migrate

setup-web: ## [3/5] Provision Nginx & Storage Structure
	@mkdir -p storage/logs storage/uploads/nats/{ingest,process,archive} storage/vectors
	@echo "✅ Storage structure verified."

setup-python: ## [4/5] Setup VirtualEnv, Requirements, and Warm-up Model
	@echo "🐍 Initializing Python..."
	@python3 -m venv $(VENV)
	@$(PIP) install --upgrade pip
	@$(PIP) install -r requirements.txt
	@echo "🧠 Pre-loading Neural Model (all-MiniLM-L6-v2)..."
	@$(PYTHON) -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('all-MiniLM-L6-v2')"
	@echo "✅ Python environment ready."

run-worker: ## 🚀 Start the NATS-Lite Neural Worker
	@echo "📦 Starting Neural Worker..."
	@export PYTHONPATH=$(PROJECT_PATH)/pymvc; \
	$(PYTHON) -m app.nats_worker

logs: ## 📋 Tailing Nginx, PHP, and Neural logs
	@tail -f storage/logs/*.log /var/log/nginx/sharpishly_access.log

setup-test-job: ## 🧪 Create a test job via PHP endpoint
	@curl -i http://sharpishly.dev/php/job/create