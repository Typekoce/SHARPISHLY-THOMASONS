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
migrate: ## [1/5] Run PHP database migrations via entry point
	@echo "🗄️ Running Database Migrations..."
	@curl -s -i http://$(DOMAIN)/php/scaffold/migrate | grep "HTTP/1.1"

# --- Monitoring ---
.PHONY: logs
logs: ## [2/5] Tail Nginx and Project logs
	@tail -f $(STORAGE_PATH)/logs/*.log /var/log/nginx/sharpishly_access.log

# --- Ingestion Pipeline (New) ---
.PHONY: ingest
ingest: ## [New] Snapshot a form (Usage: make ingest URL=https://target.com)
	@echo "📸 Snapshotting form from: $(URL)..."
	@curl -i "http://$(DOMAIN)/php/ingestion/?query=$(URL)"

.PHONY: vectorize
vectorize: ## [New] Prepare snapshots for vector embedding
	@echo "🧠 Vectorizing form snapshots..."
	@$(PYTHON) ./pymvc/app/vectorize_form.py

# --- Neural Workers (Python) ---
.PHONY: run-worker run-email-worker run-rag
run-worker: ## [3/5] Start the NATS-Lite Neural Worker
	@echo "🚀 Starting Neural Worker..."
	@export PYTHONPATH=$(ROOT_DIR)/pymvc; $(PYTHON) -m app.nats_worker

.PHONY: run-email-worker
run-email-worker: ## [3/5] Start the Email Job Watcher Worker
	@echo "🚀 Starting Email Worker..."
	@if [ -f venv/bin/python ]; then \
		echo "→ Launching with venv..."; \
		nohup ./venv/bin/python pymvc/app/email_worker.py > storage/logs/email_worker.log 2>&1 & \
		echo "✅ Email worker started in background. Check storage/logs/email_worker.log"; \
	else \
		echo "❌ Virtual environment not found at venv/. Run ./install.sh first."; \
		exit 1; \
	fi

.PHONY: run-rag
run-rag:
	@./pymvc/app/rag_start.sh

# --- Testing & Inspection ---
.PHONY: test-job check-ingest check-email-queue
test-job: ## [4/5] Create a test job via PHP endpoint
	@curl -i http://$(DOMAIN)/php/job/create

check-ingest: ## Inspect the NATS ingest folder
	@ls -l $(STORAGE_PATH)/uploads/nats/ingest/

check-email-queue: ## Inspect the Email waiting folder
	@ls -l $(STORAGE_PATH)/agents/emails/waiting/

# --- Reset Vector DB ---
reset-db: ## [5/5] Wipe vector store for fresh start
	@/usr/bin/python3 ./pymvc/app/reset_vector_db.py