# --- CONSTANTS ---
COMPOSE = docker compose
PHP_CONT = sharpishly-php
AI_CONT = sharpishly-ai-api

.DEFAULT_GOAL := help

# --- TARGETS ---
test-infra: ## Run the Infrastructure Handshake audit (Env & DB)
	@echo "🚀 Stage 1: Auditing Environment Variables..."
	@docker exec $(PHP_CONT) php /var/www/html/tests/php/src/Services/EnvironmentServiceTest.php
	@echo "\n🚀 Stage 2: Auditing Service Connectivity..."
	@docker exec $(PHP_CONT) php /var/www/html/tests/php/src/Services/DbServiceTest.php --audit

.PHONY: help
help: ## Display this help menu
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Build and start all containers in the background
	$(COMPOSE) up -d --build

down: ## Stop and remove all containers
	$(COMPOSE) down

restart: down up ## Full reset: Stop, then Start

test: ## Run the entire Neural Handshake test suite (PHP & Python)
	@echo "🧪 Running PHP Unit Tests..."
	docker exec $(PHP_CONT) vendor/bin/phpunit tests/php
	@echo "🐍 Running Python Unit Tests..."
	docker exec $(AI_CONT) pytest tests/ai

logs: ## Tail all logs to monitor the Neural Handshake
	$(COMPOSE) logs -f

clean: ## Nuke volumes, orphans, and images (The 'Emergency' Reset)
	$(COMPOSE) down -v --remove-orphans

sh-php: ## Drop into the PHP container shell
	docker exec -it $(PHP_CONT) sh

sh-ai: ## Drop into the AI container shell
	docker exec -it $(AI_CONT) sh
