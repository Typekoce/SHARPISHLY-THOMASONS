# SHARPISHLY-THOMASONS: Native Provisioning (Debian 12)
# --- SETTINGS ---
VENV           := ./venv
PYTHON         := $(VENV)/bin/python3
PIP            := $(VENV)/bin/python3 -m pip
PROJECT_PATH   := $(shell pwd)
PHP_LOG        := /var/log/php8.2-fpm.log
NEURAL_LOG     := storage/logs/neural_processor.log

# Load variables from .env if it exists
ifneq (,$(wildcard ./.env))
    include .env
    export $(shell sed 's/=.*//' .env)
endif

# Dynamic DB credentials extracted via PHP bridge
DB_NAME := $(shell php extract_env.php DB_NAME 2>/dev/null)
DB_USER := $(shell php extract_env.php DB_USER 2>/dev/null)
DB_PASS := $(shell php extract_env.php DB_PASS 2>/dev/null)

.PHONY: help install-lemp setup-db setup-web setup-python fix-permissions logs run-worker setup-test-job all

help: ## Show this help message
	@echo "SHARPISHLY-THOMASONS Management Commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# --- CORE PROVISIONING ---

install-lemp: ## [1/5] Install LEMP stack, Python, and SSH
	@echo "--- Installing Dependencies ---"
	@sudo apt-get update -y
	@sudo apt-get install -y git nginx mariadb-server php-fpm php-mysql \
		python3 python3-venv python3-pip curl openssh-server samba php8.2-curl
	@grep -q "sharpishly.dev" /etc/hosts || echo "127.0.0.1 sharpishly.dev" | sudo tee -a /etc/hosts

setup-db: ## [2/5] Initialize MariaDB database and user
	@echo "🚀 Starting MariaDB Initialization..."
	@sudo systemctl start mariadb
	@sudo mariadb -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	@sudo mariadb -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'localhost' IDENTIFIED BY '$(DB_PASS)';"
	@sudo mariadb -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'localhost';"
	@sudo mariadb -e "FLUSH PRIVILEGES;"
	@echo "✅ Database '$(DB_NAME)' is live."

setup-web: ## [3/5] Provision Nginx & Storage Structure
	@echo "🌐 Provisioning Native Nginx & Storage..."
	@# Path traversal permissions for Nginx
	@sudo chmod +x $(HOME) $(HOME)/Documents $(PROJECT_PATH)
	@# Nginx Config
	@sudo rm -f /etc/nginx/sites-enabled/default
	@sudo cp ./infra/nginx/default.conf /etc/nginx/sites-available/sharpishly
	@sudo ln -sf /etc/nginx/sites-available/sharpishly /etc/nginx/sites-enabled/
	@# Create NATS-Lite structure
	@mkdir -p storage/logs storage/vectors storage/uploads/nats/ingest \
		storage/uploads/nats/process storage/uploads/nats/results
	@touch $(NEURAL_LOG) storage/logs/app.log
	@# Grounding: Convert Docker hostnames to local IP
	@grep -rlE "sharpishly(-db|-redis|-ollama)" ./web/php/src/ | xargs -r sed -i 's/sharpishly(-db|-redis|-ollama)/127.0.0.1/g'
	@# Apply Permissions
	@$(MAKE) fix-permissions
	@sudo nginx -t && sudo systemctl restart nginx php8.2-fpm

setup-python: ## [4/5] Setup VirtualEnv and requirements
	@echo "🐍 Initializing Python..."
	@python3 -m venv $(VENV)
	@$(PIP) install --upgrade pip
	@$(PIP) install -r requirements.txt
	@echo "✅ Python environment ready."

# --- MAINTENANCE & PERMISSIONS ---

fix-permissions: ## 🔐 Apply permanent SetGID & Group permissions
	@echo "🛠️  Applying SetGID (2775) to storage..."
	@sudo usermod -a -G www-data $(USER)
	@sudo chown -R $(USER):www-data $(PROJECT_PATH)/storage
	@find $(PROJECT_PATH)/storage -type d -exec chmod 2775 {} +
	@find $(PROJECT_PATH)/storage -type f -exec chmod 664 {} +
	@chmod 664 $(NEURAL_LOG) storage/logs/app.log
	@echo "✅ Permissions grounded."

# --- RUNNERS & LOGS ---

run-worker: ## 🚀 Start the NATS-Lite Neural Worker
	@echo "📦 Starting Neural Worker (listening on nats/)..."
	@export PYTHONPATH=$(PROJECT_PATH)/pymvc; \
	$(PYTHON) -m app.nats_worker

logs: ## 📋 Tailing Nginx, PHP, and Neural logs
	@echo "📋 Tailing Unified Logs... (Ctrl+C to stop)"
	@sudo tail -f /var/log/nginx/sharpishly_access.log \
	             /var/log/nginx/sharpishly_error.log \
	             $(PHP_LOG) \
	             $(NEURAL_LOG)

setup-test-job: ## 🧪 Create a test job via PHP endpoint
	@echo "--- Creating test job ---"
	@curl -i http://sharpishly.dev/php/job/create

all: install-lemp setup-db setup-web setup-python fix-permissions ## Execute full provisioning flow