# SHARPISHLY-THOMASONS: Native Provisioning
# Target: Debian 12 (Fresh ISO)
# --- SETTINGS ---
VENV := ./venv
PYTHON := $(VENV)/bin/python3
PIP := $(VENV)/bin/python3 -m pip
PROJECT_PATH := $(shell pwd)
PHP_LOG := /var/log/php8.2-fpm.log  # Adjust version if using 8.1/8.3
NEURAL_LOG = storage/logs/neural_processor.log

.PHONY: help purge-docker install setup-samba setup-db setup-web setup-python create-env all

# Load variables from .env if it exists
ifneq (,$(wildcard ./.env))
    include .env
    export $(shell sed 's/=.*//' .env)
endif

help: ## Show this help message
	@echo "SHARPISHLY-THOMASONS Management Commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# --- SSH Key Management ---
show-key: ## Find and display the first local public SSH key
	@PUB_KEY=$$(ls ~/.ssh/*.pub 2>/dev/null | head -n 1); \
	if [ -z "$$PUB_KEY" ]; then \
		echo "❌ No public SSH keys found in ~/.ssh/"; \
		exit 1; \
	else \
		echo "🔑 Found public key: $$PUB_KEY"; \
		echo "---------------------------------------------------"; \
		cat "$$PUB_KEY"; \
		echo -e "\n---------------------------------------------------"; \
	fi

purge-docker: ## [0/5] Remove Docker and all its traces
	@echo "--- Purging Docker ---"
	@if command -v docker > /dev/null; then \
		sudo apt-get purge -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin || true; \
		sudo rm -rf /var/lib/docker /var/lib/containerd; \
	else \
		echo "Docker not found. Skipping."; \
	fi

install-lemp: ## [1/5] Install LEMP stack, Python, and SSH
	@echo "--- Installing Dependencies ---"
	@sudo apt-get update -y
	@sudo apt-get install -y -o Dpkg::Progress-Fancy="1" \
		git nginx mariadb-server php-fpm php-mysql \
		python3 python3-venv python3-pip curl openssh-server samba
	@echo "127.0.0.1 sharpishly.dev" | sudo tee -a /etc/hosts
	@if [ ! -f ~/.ssh/id_ed25519 ]; then \
		ssh-keygen -t ed25519 -C "vboxuser@Debian12-TARDIS" -f ~/.ssh/id_ed25519 -N ""; \
	fi
	@echo "--- SSH KEY FOR GITHUB ---"
	@cat ~/.ssh/id_ed25519.pub
	@echo "--------------------------"
	@read -p "Add key to GitHub, then press Enter to clone..." confirm
	@mkdir -p ~/Documents
	@if [ ! -d ~/Documents/SHARPISHLY-THOMASONS ]; then \
		git clone git@github.com:Typekoce/SHARPISHLY-THOMASONS.git ~/Documents/SHARPISHLY-THOMASONS; \
	fi

create-env: ## Create a local .env file from template
	@if [ ! -f .env ]; then \
		echo "DB_NAME=sharpishly_db\nDB_USER=vboxuser\nDB_PASS=your_password\nDB_HOST=sharpishly" > .env; \
		echo ".env created. Please edit before running setup-db."; \
	else \
		echo ".env exists."; \
	fi

# 1. Extract values using the actual filename you created
DB_NAME := $(shell php extract_env.php DB_NAME)
DB_USER := $(shell php extract_env.php DB_USER)
DB_PASS := $(shell php extract_env.php DB_PASS)

# 2. Update setup-db target
setup-db: ## [2/5] Initialize MariaDB database and user
	@echo "🚀 Starting MariaDB Initialization..."
	
	@# Verify the bridge worked
	@if [ -z "$(DB_NAME)" ]; then echo "❌ Error: Could not extract DB_NAME via extract.php"; exit 1; fi

	@printf "   [▓▓░░░░░░░░] 20%% Starting Service..."
	@sudo systemctl start mariadb
	
	@printf "\r   [▓▓▓▓░░░░░░] 40%% Creating Database ($(DB_NAME))..."
	@sudo mariadb -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	
	@printf "\r   [▓▓▓▓▓▓░░░░] 60%% Creating User ($(DB_USER))..."
	@# Native grounding: Grant for both localhost and 127.0.0.1
	@sudo mariadb -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'localhost' IDENTIFIED BY '$(DB_PASS)';"
	@sudo mariadb -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'127.0.0.1' IDENTIFIED BY '$(DB_PASS)';"
	
	@printf "\r   [▓▓▓▓▓▓▓▓░░] 80%% Granting Privileges..."
	@sudo mariadb -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'localhost';"
	@sudo mariadb -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'127.0.0.1';"
	@sudo mariadb -e "FLUSH PRIVILEGES;"
	
	@printf "\r   [▓▓▓▓▓▓▓▓▓▓] 100%% Handshake Complete!               \n"
	@echo "-------------------------------------------------------"
	@echo "✅ SUCCESS: Database '$(DB_NAME)' is live."
	@echo "-------------------------------------------------------"

setup-web: ## [3/5] Provision Nginx & permissions (Native Debian)
	@echo "🌐 Provisioning Native Nginx..."
	@# 1. Traversal Permissions
	@sudo chmod +x $(shell dirname $(shell dirname $(PROJECT_PATH)))
	@sudo chmod +x $(shell dirname $(PROJECT_PATH))
	@sudo chmod +x $(PROJECT_PATH)
	
	@# 2. Nginx Configuration & Ghost Cleanup
	@# Removed duplicate symlink attempts; clean slate approach
	@sudo rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-enabled/default.conf /etc/nginx/sites-enabled/sharpishly
	@sudo cp ./infra/nginx/default.conf /etc/nginx/sites-available/sharpishly
	@sudo ln -sf /etc/nginx/sites-available/sharpishly /etc/nginx/sites-enabled/
	@sudo nginx -t && sudo systemctl restart nginx php8.2-fpm

	@# 3. Grounding: Purge Docker Hostnames
	@echo "🧹 Purging Docker container artifacts..."
	@# Using -r on xargs to prevent errors if no files match
	@grep -rlE "sharpishly(-db|-redis|-ollama)" $(PROJECT_PATH)/web/php/src/ | xargs -r sed -i -E 's/sharpishly(-db|-redis|-ollama)/127.0.0.1/g'
	@sed -i "s/Is the 'sharpishly-db' container running?/Is the MariaDB service running?/g" $(PROJECT_PATH)/web/php/src/Services/Db.php 2>/dev/null || true

	@# 4. Storage Provisioning & Scoped Permissions
	@echo "🔐 Applying Setgid and scoped permissions..."
	@# Unified directory creation
	@sudo mkdir -p $(PROJECT_PATH)/storage/uploads $(PROJECT_PATH)/storage/logs $(PROJECT_PATH)/storage/database/vectors
	@sudo touch $(PROJECT_PATH)/storage/logs/app.log
	
	@# Ownership and Standard Permissions
	@sudo chown -R $(USER):www-data $(PROJECT_PATH)/web $(PROJECT_PATH)/storage
	@find $(PROJECT_PATH)/web -type d -exec chmod 755 {} +
	@find $(PROJECT_PATH)/web -type f -exec chmod 644 {} +
	
	@# Storage Specific: 775 + Setgid
	@chmod -R 775 $(PROJECT_PATH)/storage
	@find $(PROJECT_PATH)/storage -type d -exec chmod g+s {} +
	@chmod 664 $(PROJECT_PATH)/storage/logs/app.log

	@# 5. Local DNS Mapping (Deduplicated)
	@# Check if entry exists before appending to prevent bloating /etc/hosts
	@grep -q "sharpishly.dev" /etc/hosts || echo "127.0.0.1 sharpishly.dev crm.sharpishly.dev cyberdeck.sharpishly.dev" | sudo tee -a /etc/hosts > /dev/null

	@# 6. Install PHP-curl
	@sudo apt update
	@sudo apt install php8.2-curl
	@sudo systemctl restart php8.2-fpm

	@# 7. Vector storage
	@sudo mkdir -p storage/vectors
	@sudo chmod -R 775 storage/vectors

    @sudo mkdir -p storage/uploads/nats/process storage/uploads/nats/results
	@sudo chmod -R 775 storage/uploads/nats

	@echo "-------------------------------------------------------"
	@echo "✅ SUCCESS: Environment Grounded"
	@echo "🔗 API: http://sharpishly.dev/php/health"
	@echo "-------------------------------------------------------"

setup-db-migration: ## [4/5] Setup Sharpishly database tables
	@echo "🚀 Starting database migration..."
	@curl -s -f -i http://localhost/php/scaffold/migrate || (echo "❌ Migration failed. Run 'make logs' to see why." && exit 1)


setup-python: ## [5/6] Setup Python VirtualEnv and install requirements
	@echo "🐍 Initializing Python Virtual Environment..."
	@python3 -m venv $(VENV)
	@$(PIP) install --upgrade pip
	@$(PIP) install --progress-bar pretty -r requirements.txt
	@echo "✅ Python environment ready."

logs: ## [6/6] Display error and access logs (Nginx & PHP)
	@echo "📋 Tailing Nginx and PHP logs... (Ctrl+C to stop)"
	@sudo tail -f /var/log/nginx/sharpishly_access.log \
	             /var/log/nginx/sharpishly_error.log \
	             $(PHP_LOG) \
	             $(NEURAL_LOG)

setup-test-job: ## [7/7] Createing test job 001_job.json
# --- Test Neural Pipeline ---
	@echo "--- Create test job 001_job.json ---"
	@curl -i http://sharpishly.dev/php/job/create

setup-samba: ## Configure Samba for host-to-guest file sharing
	@echo "--- Configuring Samba ---"
	@if ! grep -q "\[SHARPISHLY\]" /etc/samba/smb.conf; then \
		printf "\n[SHARPISHLY]\n   path = /home/vboxuser/Documents/SHARPISHLY-THOMASONS\n   browseable = yes\n   read only = no\n   guest ok = no\n   valid users = vboxuser\n   force user = vboxuser\n   create mask = 0775\n   directory mask = 0775\n" | sudo tee -a /etc/samba/smb.conf; \
		sudo smbpasswd -a vboxuser; \
		sudo systemctl restart smbd; \
	fi

# --- Ollama Installation & Service ---
install-ollama: ## [Step 0] Install Ollama natively on Debian
	@echo "📥 Downloading and installing Ollama..."
	@curl -fsSL https://ollama.com/install.sh | sh
	@echo "✅ Ollama installed."
	@echo "🔄 Starting Ollama service..."
	@sudo systemctl enable --now ollama
	@echo "🟢 Ollama is now running as a system service."

# --- Model Pulling Targets ---
llm-heavy: ## VM DEVELOPMENT: Pull heavy models (Llama 3.1 + Nomic Embed)
	@echo "🚀 Initializing Heavy Stack (Dev Mode)..."
	@# Verify ollama is installed before pulling
	@command -v ollama >/dev/null 2>&1 || { echo "❌ Ollama not found. Run 'make install-ollama' first."; exit 1; }
	ollama pull llama3.1
	ollama pull nomic-embed-text
	@echo "✅ Heavy models ready."

# --- Model Management (Native) ---

llm-lean: ## HOST PRODUCTION: Pull lean models (Phi-3 mini + all-minilm)
	@echo "🧠 Initializing Lean Stack (Production Mode)..."
	@command -v ollama >/dev/null 2>&1 || { echo "❌ Ollama not found. Run 'make install-ollama' first."; exit 1; }
	ollama pull phi3:mini
	ollama pull all-minilm
	@echo "✅ Lean models ready."

llm-status: ## Show currently downloaded local models
	@echo "📊 Local Ollama Model Library:"
	@command -v ollama >/dev/null 2>&1 || { echo "❌ Ollama not found."; exit 1; }
	@ollama list

clean-models: ## Remove all local Ollama models (BE CAREFUL)
	@echo "⚠️  WARNING: This will delete your local Ollama library."
	@read -p "Are you sure you want to delete all models? [y/N] " ans && [ "$$ans" = "y" ] || (echo "Aborted."; exit 1)
	@ollama list | tail -n +2 | awk '{print $$1}' | xargs -I {} ollama rm {}
	@echo "✅ Ollama library wiped."

llm-info: ## Show Ollama version and system info
	@echo "🔍 Ollama System Information:"
	@command -v ollama >/dev/null 2>&1 || { echo "❌ Ollama not found."; exit 1; }
	@ollama --version
	@echo ""
	@ollama list

# --- RAG (Retrieval-Augmented Generation) Section ---

rag-install: ## [Step 4/5] Create Venv and install RAG dependencies
	@if [ ! -d "$(VENV)" ]; then \
		echo "🌱 Creating virtual environment..."; \
		python3 -m venv $(VENV); \
	fi
	@echo "📦 Installing RAG dependencies into Venv..."
	@$(PIP) install --upgrade pip
	@$(PIP) install langchain langchain-ollama langchain-chroma langchain-community pypdf chromadb
	@echo "✅ RAG dependencies installed."

rag-index-heavy: ## Index documents in ./docs/ using Heavy stack
	@mkdir -p ./docs scripts
	@echo "📚 Generating Heavy Indexer Script..."
	@printf 'from langchain_community.document_loaders import DirectoryLoader, PyPDFLoader\n\
from langchain_text_splitters import RecursiveCharacterTextSplitter\n\
from langchain_ollama import OllamaEmbeddings\n\
from langchain_chroma import Chroma\n\
import os\n\n\
loader = DirectoryLoader("./docs", glob="**/*.pdf", loader_cls=PyPDFLoader)\n\
docs = loader.load()\n\
if not docs: print("❌ No PDFs found in ./docs"); exit()\n\n\
text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=200)\n\
splits = text_splitter.split_documents(docs)\n\
embeddings = OllamaEmbeddings(model="nomic-embed-text")\n\
vectorstore = Chroma.from_documents(\n\
    documents=splits,\n\
    embedding=embeddings,\n\
    persist_directory="./chroma_db_heavy"\n\
)\n\
print("✅ Heavy RAG index created/updated in ./chroma_db_heavy")\n' > scripts/index_heavy.py
	@$(PYTHON) scripts/index_heavy.py
	@echo "Heavy RAG indexing complete."

rag-chat: ## Start interactive RAG chat (Heavy config)
	@mkdir -p scripts
	@echo "💬 Generating RAG Chat Script..."
	@printf 'import sys\n\
from langchain_ollama import ChatOllama, OllamaEmbeddings\n\
from langchain_chroma import Chroma\n\
from langchain_core.prompts import ChatPromptTemplate\n\
from langchain_core.runnables import RunnablePassthrough\n\
from langchain_core.output_parsers import StrOutputParser\n\n\
embeddings = OllamaEmbeddings(model="nomic-embed-text")\n\
vectorstore = Chroma(persist_directory="./chroma_db_heavy", embedding_function=embeddings)\n\
retriever = vectorstore.as_retriever(search_kwargs={"k": 4})\n\
llm = ChatOllama(model="llama3.1", temperature=0.3)\n\
template = """Answer the question based only on the following context:\\n{context}\\nQuestion: {question}"""\n\
prompt = ChatPromptTemplate.from_template(template)\n\
chain = ({"context": retriever, "question": RunnablePassthrough()} | prompt | llm | StrOutputParser())\n\n\
print("RAG Chat ready! (type exit to quit)\\n")\n\
while True:\n\
    try:\n\
        q = input("You: ")\n\
        if q.lower() in ["exit", "quit"]: break\n\
        print("Assistant:", chain.invoke(q))\n\
        print("-" * 60)\n\
    except KeyboardInterrupt: break\n' > scripts/chat_heavy.py
	@$(PYTHON) scripts/chat_heavy.py

all: purge-docker install setup-samba setup-db setup-python setup-web ## Execute the entire provisioning flow

