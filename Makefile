# --- CONSTANTS ---
COMPOSE   = docker compose
PHP_CONT  = sharpishly-php
AI_CONT   = sharpishly-ai-api
LLM_CONT  = sharpishly-ollama

.DEFAULT_GOAL := help

# --- TARGETS ---

# --- Docker Environment Setup ---

docker-setup: ## Configure docker permissions and initialize storage for a new machine
	@echo "🔧 Starting environment setup..."
	@# Add user to docker group if not already there
	@if ! groups | grep -q "\bdocker\b"; then \
		echo "👥 Adding $(USER) to docker group..."; \
		sudo usermod -aG docker $(USER); \
		echo "⚠️  Please log out and log back in (or run 'newgrp docker') to apply group changes."; \
	else \
		echo "✅ User already in docker group."; \
	fi
	@# Initialize storage and logs to prevent Nginx/PHP crashes
	@echo "📂 Initializing storage directories..."
	mkdir -p storage/log storage/uploads storage/framework/views
	touch storage/log/nginx_access.log storage/log/nginx_error.log
	@# Set permissions so Docker containers can write to these volumes
	chmod -R 777 storage
	@echo "🚀 Setup complete. Try running 'make up' next."

# --- Existing Commands ---

up:
	docker compose up -d --build

down:
	docker compose down

logs-storage:
	tail -f storage/log/*.log

show-key: ## Find and display the local public SSH key for Digital Ocean / GitHub
	@PUB_KEY=$$(ls ~/.ssh/*.pub 2>/dev/null | head -n 1); \
	if [ -z "$$PUB_KEY" ]; then \
		echo "❌ No public SSH keys found in ~/.ssh/"; \
	else \
		echo "🔑 Found Key: $$PUB_KEY"; \
		echo "---------------------------------------------------"; \
		cat "$$PUB_KEY"; \
		echo -e "\n---------------------------------------------------"; \
	fi

pull-heavy: ## VM DEVELOPMENT: Pull Llama 3.1 & Nomic Embed (Heavy)
	@echo "🚀 Pulling Heavy LLM: llama3.1 (~4.7GB)..."
	docker exec -it $(LLM_CONT) ollama pull llama3.1
	@echo "🚀 Pulling Heavy Embedder: nomic-embed-text (~274MB)..."
	docker exec -it $(LLM_CONT) ollama pull nomic-embed-text
	@echo "✅ Heavy Stack Pulled."

pull-lean: ## HOST PRODUCTION: Pull Phi3 & all-minilm (Lean) optimized for 1GB RAM
	@echo "🧠 Pulling Lean LLM: phi3:mini (~2.3GB)..."
	docker exec -it $(LLM_CONT) ollama pull phi3:mini
	@echo "🧠 Pulling Lean Embedder: all-minilm (~45MB)..."
	docker exec -it $(LLM_CONT) ollama pull all-minilm
	@echo "✅ Lean Stack Pulled."

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
	@sudo chmod -R 777 storage/

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
	docker system prune -a --volumes -f
	docker builder prune -a -f
	docker image prune -f
	@echo "Environment sanitized. Storage rmains (mapped to host-side)"

sh-php: ## Drop into the PHP container shell
	docker exec -it $(PHP_CONT) sh

sh-ai: ## Drop into the AI container shell
	docker exec -it $(AI_CONT) sh

sh-llm: ## Drop into the Ollama container shell
	docker exec -it $(LLM_CONT) sh

logs-storage: ## Nginx, PHP & MySQL logs
	@tail -f storage/log/*.log

db-migrate: ## Migrate the sharpishly db by calling http://localhost:8080/php/scaffold/migrate
	@curl -i http://localhost:8080/php/scaffold/migrate

git-push: ## Git add, commit with message & git push. Usage: make git-push m="Message"
	git add .
	git commit -m "$(m)"
	git push origin $(shell git rev-parse --abbrev-ref HEAD)
