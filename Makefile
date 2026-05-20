# --- Variables ---
ROOT_DIR     := $(shell pwd)
STORAGE_PATH := $(ROOT_DIR)/storage
PYTHON       := $(ROOT_DIR)/venv/bin/python3
DOMAIN       := sharpishly.dev

# --- Help ---
.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

# --- Database & Migrations ---
.PHONY: migrate
migrate: ## [1/4] Run PHP database migrations via entry point
	@echo "🗄️ Running Database Migrations..."
	@curl -s -i http://$(DOMAIN)/php/scaffold/migrate | grep "HTTP/1.1"

# --- Monitoring ---
.PHONY: logs
logs: ## [2/4] Tail Nginx and Project logs
	@tail -f $(STORAGE_PATH)/logs/*.log /var/log/nginx/sharpishly_access.log

# --- Neural Worker (NATS) ---
.PHONY: run-worker
run-worker: ## [3/4] Start the NATS-Lite Neural Worker (Python)
	@echo "🚀 Starting Neural Worker..."
	@export PYTHONPATH=$(ROOT_DIR)/pymvc; $(PYTHON) -m app.nats_worker

# --- Testing & Inspection ---
.PHONY: test-job check-ingest
test-job: ## [4/4] Create a test job via PHP endpoint
	@curl -i http://$(DOMAIN)/php/job/create

check-ingest: ## Inspect the NATS ingest folder for pending jobs
	@ls -l $(STORAGE_PATH)/uploads/nats/ingest/

# --- Neural Path (RAG) ---
.PHONY: run-rag
run-rag: ## [NEW] Start the RAG Microservice (Python)
	@echo "🧠 Starting RAG Microservice on port 8765..."
	@export PYTHONPATH=$(ROOT_DIR)/pymvc; nohup $(PYTHON) -m app.rag_service > $(STORAGE_PATH)/logs/rag_service.log 2>&1 &
	@echo "🚀 RAG service running in background. Check $(STORAGE_PATH)/logs/rag_service.log for output."
