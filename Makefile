# --- CONSTANTS ---
COMPOSE   = docker compose
PHP_CONT  = sharpishly-php
AI_CONT   = sharpishly-ai-worker
LLM_CONT  = sharpishly-ollama
DB_CONT   = sharpishly-db

# Set STABILIZE=true to enable slow-boot for external drives/weak I/O
STABILIZE ?= false

.DEFAULT_GOAL := help

# --- Docker Environment Setup ---

docker-setup: ## Configure permissions and initialize storage
	@echo "🔧 Starting environment setup..."
	@if ! groups | grep -q "\bdocker\b"; then \
		echo "👥 Adding $(USER) to docker group..."; \
		sudo usermod -aG docker $(USER); \
	fi
	@echo "📂 Initializing storage directories..."
	mkdir -p storage/log storage/uploads storage/framework/views
	touch storage/log/nginx_access.log storage/log/nginx_error.log
	chmod -R 777 storage
	@echo "🚀 Setup complete."

# --- Core Infrastructure ---

up: ## Build and start all containers. Use STABILIZE=true for slow I/O.
	@mkdir -p storage/log storage/uploads
	@chmod -R 777 storage
	@if [ "$(STABILIZE)" = "true" ]; then \
		echo "⏳ Stabilizing I/O: Starting Database first..."; \
		$(COMPOSE) up -d $(DB_CONT); \
		sleep 30; \
	fi
	$(COMPOSE) up -d --build
	@chmod -R 777 storage
	@echo "✅ Stack is up."

down: ## Stop and remove all containers
	$(COMPOSE) down

restart: down up ## Full reset: Stop, then Start

clean: ## Nuke volumes, orphans, and build cache
	$(COMPOSE) down -v --remove-orphans
	docker system prune -f
	@echo "✨ Environment sanitized."

# --- Development & Debugging ---

logs: ## Tail all container logs
	$(COMPOSE) logs -f

logs-storage: ## Tail local file logs (Nginx, PHP, App)
	@tail -f storage/log/*.log

sh-php: ## Shell into PHP container
	docker exec -it $(PHP_CONT) sh

sh-ai: ## Shell into AI Worker container
	docker exec -it $(AI_CONT) sh

sh-llm: ## Shell into Ollama container
	docker exec -it $(LLM_CONT) sh

dBug: ## Quick check of the vectors table
	docker exec -it $(DB_CONT) mysql -u root -p -e "USE sharpishly; SELECT * FROM vectors LIMIT 5;"

# --- Neural Handshake & LLM ---

pull-heavy: ## VM: Pull Llama 3.1 & Nomic Embed
	docker exec -it $(LLM_CONT) ollama pull llama3.1
	docker exec -it $(LLM_CONT) ollama pull nomic-embed-text

pull-lean: ## PROD: Pull Phi3 & all-minilm
	docker exec -it $(LLM_CONT) ollama pull phi3:mini
	docker exec -it $(LLM_CONT) ollama pull all-minilm

ps-ai: ## Monitor Ollama's active models
	docker exec -it $(LLM_CONT) ollama ps

test: ## Run the entire Neural Handshake test suite
	@echo "🧪 Running PHP Infrastructure Handshake..."
	@docker exec $(PHP_CONT) php /var/www/html/tests/php/src/Services/EnvironmentServiceTest.php
	@echo "🐍 Running Python Unit Tests..."
	@docker exec $(AI_CONT) pytest tests/ai

# --- Utilities ---

db-migrate: ## Trigger database migrations via web endpoint
	@curl -i http://localhost:8080/php/scaffold/migrate

job-create: ## Create a test job by triggering Path B (Cleaning)
	@curl -s http://localhost:8080/php/job/create | jq . || curl -i http://localhost:8080/php/job/create

job-pending: ## Fetch the next pending job (Verify what the AI Worker sees)
	@curl -s http://localhost:8080/php/job/index | jq . || curl -i http://localhost:8080/php/job/index

show-key: ## Display local public SSH key
	@PUB_KEY=$$(ls ~/.ssh/*.pub 2>/dev/null | head -n 1); \
	if [ -z "$$PUB_KEY" ]; then echo "❌ No keys found"; else cat "$$PUB_KEY"; fi

git-push: ## Git add, commit & push. Usage: make git-push m="Message"
	clear
	git add .
	git commit -m "$(m)"
	git push origin $(shell git rev-parse --abbrev-ref HEAD)

help: ## Display this help menu
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

.PHONY: help up down restart clean logs logs-storage sh-php sh-ai sh-llm pull-heavy pull-lean ps-ai test db-migrate show-key git-push