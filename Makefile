# --- Variables ---
PROJECT_PATH := $(shell pwd)
STORAGE_PATH := $(PROJECT_PATH)/storage
VENV := venv
PYTHON := $(PROJECT_PATH)/$(VENV)/bin/python3
PIP := $(PROJECT_PATH)/$(VENV)/bin/pip
DB_NAME := sharpishly
DB_USER := sharpadmin
DB_PASS := sharpish_pass_2026

# Get the absolute path of the directory containing the Makefile
ROOT_DIR := $(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))
CURRENT_USER := $(shell whoami)

# --- Primary Commands ---
.PHONY: help all fix-permissions setup-sys setup-hosts setup-nginx setup-db setup-db-migration setup-web setup-python run-worker logs setup-test-job check-ingest

help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

all: setup-sys setup-hosts setup-nginx setup-db setup-web setup-python fix-permissions ## Execute full provisioning flow

fix-permissions: ## 🔐 Apply permanent SetGID & Group permissions (Portable)
	@echo "🛠️  Grounding permissions at $(ROOT_DIR)/storage..."
	@mkdir -p $(ROOT_DIR)/storage
	@sudo chown -R $(CURRENT_USER):www-data $(ROOT_DIR)/storage
	@sudo chmod -R 2775 $(ROOT_DIR)/storage
	@sudo find $(ROOT_DIR)/storage -type d -exec chmod g+s {} +
	@echo "✅ Permissions grounded for user '$(CURRENT_USER)'."

setup-sys: ## [1/5] Install LEMP stack, Python, and MariaDB
	@sudo apt update && sudo apt install -y nginx mariadb-server php-fpm php-mysql python3-venv python3-pip curl
	@sudo systemctl enable nginx
	@sudo systemctl enable mariadb

setup-hosts: ## [1.1/5] 🌐 Map sharpishly.dev to localhost (requires sudo)
	@echo "🌐 Checking local DNS for sharpishly.dev..."
	@if grep -q "sharpishly.dev" /etc/hosts; then \
		echo "✅ Host already mapped."; \
	else \
		echo "🛠️  Adding sharpishly.dev to /etc/hosts..."; \
		echo "127.0.0.1    sharpishly.dev" | sudo tee -a /etc/hosts > /dev/null; \
		echo "✅ Host mapped successfully."; \
	fi

setup-nginx: ## [1.2/5] 🌐 Provision Nginx by patching infra/ config
	@echo "🌐 Provisioning Nginx for $(CURRENT_USER) on Ubuntu 24.04..."
	@cp infra/nginx/default.conf /tmp/sharpishly.conf
	@# Patch the path and PHP version for the new machine
	@sed -i "s|/home/vboxuser/Documents/SHARPISHLY-THOMASONS|$(ROOT_DIR)|g" /tmp/sharpishly.conf
	@sed -i "s|php8.2-fpm.sock|php8.3-fpm.sock|g" /tmp/sharpishly.conf
	@sudo cp /tmp/sharpishly.conf /etc/nginx/sites-available/sharpishly.dev
	@sudo ln -sf /etc/nginx/sites-available/sharpishly.dev /etc/nginx/sites-enabled/
	@sudo rm -f /etc/nginx/sites-enabled/default
	@sudo nginx -t && sudo systemctl reload nginx
	@echo "✅ Nginx provisioned and reloaded."

setup-db: setup-hosts ## [2/5] Initialize MariaDB database and user
	@echo "🚀 Initializing MariaDB..."
	@sudo mariadb -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	@sudo mariadb -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'localhost' IDENTIFIED BY '$(DB_PASS)';"
	@sudo mariadb -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'localhost';"
	@sudo mariadb -e "FLUSH PRIVILEGES;"
	@$(MAKE) setup-db-migration

setup-db-migration: ## [4/5] Run PHP database migrations
	@echo "🗄️  Running Database Migrations..."
	@# Nginx must be running for this curl to resolve
	@curl -s -i http://sharpishly.dev/php/scaffold/migrate | grep "HTTP/1.1"

setup-web: ## [3/5] Provision Nginx & Storage Structure
	@echo "📁 Creating NATS-Lite structure..."
	@mkdir -p storage/logs \
		 storage/vectors \
		 storage/uploads/nats/ingest \
		 storage/uploads/nats/process \
		 storage/uploads/nats/archive \
		 storage/uploads/nats/fail
	@touch storage/logs/laravel.log storage/logs/worker.log
	@$(MAKE) fix-permissions
	@echo "✅ Storage structure verified."

setup-python: ## [5/5] Setup VirtualEnv, Requirements, and Warm-up Model
	@echo "🐍 Initializing Python..."
	@python3 -m venv $(VENV)
	@$(PIP) install --upgrade pip
	@$(PIP) install -r requirements.txt
	@echo "🧠 Pre-loading Neural Model (all-MiniLM-L6-v2)..."
	@# Explicitly use CPU for the SF113 hardware
	@$(PYTHON) -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('all-MiniLM-L6-v2', device='cpu')"
	@echo "✅ Python environment ready."

run-worker: ## 🚀 Start the NATS-Lite Neural Worker
	@echo "📦 Starting Neural Worker..."
	@export PYTHONPATH=$(PROJECT_PATH)/pymvc; \
	$(PYTHON) -m app.nats_worker

logs: ## 📋 Tailing Nginx, PHP, and Neural logs
	@tail -f storage/logs/*.log /var/log/nginx/sharpishly_access.log

setup-test-job: ## 🧪 Create a test job via PHP endpoint
	@curl -i http://sharpishly.dev/php/job/create

check-ingest: ## 🔍 Inspect the NATS ingest folder for pending jobs
	@ls -l storage/uploads/nats/ingest/
