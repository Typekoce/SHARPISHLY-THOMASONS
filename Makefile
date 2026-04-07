# --- CONSTANTS ---
COMPOSE   = docker compose
PHP_CONT  = sharpishly-php
AI_CONT   = sharpishly-ai-api
LLM_CONT  = sharpishly-ollama

.DEFAULT_GOAL := help

# --- TARGETS ---

pull-lean: ## Pull the Micro-Neural stack (phi3 & all-minilm) optimized for 1GB RAM
	@echo "🧠 Pulling Lean LLM: phi3:mini (~2.3GB)..."
	docker exec -it $(LLM_CONT) ollama pull phi3:mini
	@echo "🧠 Pulling Lean Embedder: all-minilm (~45MB)..."
	docker exec -it $(LLM_CONT) ollama pull all-minilm
	@echo "✅ Lean Stack Pulled. Run 'make ps-ai' to monitor status."

ps-ai: ## Monitor Ollama's active models and memory footprint
	docker exec -it $(LLM_CONT) ollama ps

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
	@echo "🧪 Running PHP Infrastructure Handshake..."
	@docker exec $(PHP_CONT) php /var/www/html/tests/php/src/Services/EnvironmentServiceTest.php
	@echo "🐍 Running Python Unit Tests..."
	@docker exec $(AI_CONT) pytest tests/ai

logs: ## Tail all logs to monitor the Neural Handshake
	$(COMPOSE) logs -f

clean: ## Nuke volumes, orphans, images, and corrupted build cache
	$(COMPOSE) down -v --remove-orphans
	docker builder prune -a -f
	docker image prune -f

sh-php: ## Drop into the PHP container shell
	docker exec -it $(PHP_CONT) sh

sh-ai: ## Drop into the AI container shell
	docker exec -it $(AI_CONT) sh

sh-llm: ## Drop into the Ollama container shell
	docker exec -it $(LLM_CONT) sh

logs-storage: ## Nginx, PHP & MySQL logs
	@tail -f storage/log/*.log