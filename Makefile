# SHARPISHLY-THOMASONS: Native Provisioning
# Target: Debian 12 (Fresh ISO)

.PHONY: help purge-docker install setup-samba setup-db setup-web setup-python create-env all

# Load variables from .env if it exists
ifneq (,$(wildcard ./.env))
    include .env
    export $(shell sed 's/=.*//' .env)
endif

help: ## Show this help message
	@echo "SHARPISHLY-THOMASONS Management Commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

purge-docker: ## [0/5] Remove Docker and all its traces
	@echo "--- Purging Docker ---"
	@if command -v docker > /dev/null; then \
		sudo apt-get purge -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin || true; \
		sudo rm -rf /var/lib/docker /var/lib/containerd; \
	else \
		echo "Docker not found. Skipping."; \
	fi

install: ## [1/5] Install LEMP stack, Python, and SSH
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

setup-nats: ## [Optional] Install and configure NATS JetStream message queue
	@echo "🚀 Setting up NATS JetStream..."
	
	# Install NATS server
	@if ! command -v nats-server >/dev/null 2>&1; then \
		echo "Installing NATS server..."; \
		sudo apt-get update -y; \
		sudo apt-get install -y nats-server || { \
			echo "❌ Failed to install nats-server via apt. Installing from official binary..."; \
			curl -L https://github.com/nats-io/nats-server/releases/download/v2.10.5/nats-server-v2.10.5-linux-amd64.tar.gz | tar -xz; \
			sudo mv nats-server-v2.10.5-linux-amd64/nats-server /usr/local/bin/; \
			rm -rf nats-server-v2.10.5-linux-amd64*; \
		}; \
	else \
		echo "✅ NATS server already installed."; \
	fi

	# Create storage directory
	@mkdir -p storage/nats
	@sudo chown -R $(USER):$(USER) storage/nats
	@echo "✅ NATS storage directory created at ./storage/nats"

	# Create basic NATS config via printf
	@mkdir -p config
	@printf "listen: 0.0.0.0:4222\n\njetstream {\n    store_dir: \"./storage/nats\"\n    max_mem: 1GB\n    max_file: 10GB\n}\n\nwebsocket {\n    port: 8081\n    no_tls: true\n}\n\nlogtime: true\ndebug: false\ntrace: false\n" > config/nats.conf
	@echo "✅ NATS configuration created at config/nats.conf"

	# Create systemd service via printf
	@printf "[Unit]\nDescription=NATS Server for Sharpishly\nAfter=network.target\n\n[Service]\nType=simple\nUser=$(USER)\nExecStart=/usr/local/bin/nats-server -c $(shell pwd)/config/nats.conf\nRestart=always\nRestartSec=3\nWorkingDirectory=$(shell pwd)\nLimitNOFILE=100000\n\n[Install]\nWantedBy=multi-user.target\n" | sudo tee /etc/systemd/system/nats.service > /dev/null
	@echo "✅ NATS systemd service created"

	@sudo systemctl daemon-reload
	@sudo systemctl enable --now nats.service

	@echo "✅ NATS service installed and started"
	@echo "   → Listening on port 4222"
	@echo "   → Status: sudo systemctl status nats"

setup-samba: ## Configure Samba for host-to-guest file sharing
	@echo "--- Configuring Samba ---"
	@if ! grep -q "\[SHARPISHLY\]" /etc/samba/smb.conf; then \
		printf "\n[SHARPISHLY]\n   path = /home/vboxuser/Documents/SHARPISHLY-THOMASONS\n   browseable = yes\n   read only = no\n   guest ok = no\n   valid users = vboxuser\n   force user = vboxuser\n   create mask = 0775\n   directory mask = 0775\n" | sudo tee -a /etc/samba/smb.conf; \
		sudo smbpasswd -a vboxuser; \
		sudo systemctl restart smbd; \
	fi

create-env: ## Create a local .env file from template
	@if [ ! -f .env ]; then \
		echo "DB_NAME=sharpishly_db\nDB_USER=vboxuser\nDB_PASS=your_password" > .env; \
		echo ".env created. Please edit before running setup-db."; \
	else \
		echo ".env exists."; \
	fi

setup-db: ## [2/5] Initialize MariaDB database and user
	@echo "🚀 Starting MariaDB Initialization..."
	@printf "   [▓▓░░░░░░░░] 20%% Starting Service..."
	@sudo systemctl start mariadb
	@printf "\r   [▓▓▓▓░░░░░░] 40%% Creating Database ($(DB_NAME))..."
	@sudo mysql -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	@printf "\r   [▓▓▓▓▓▓░░░░] 60%% Creating User ($(DB_USER))..."
	@sudo mysql -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'localhost' IDENTIFIED BY '$(DB_PASS)';"
	@printf "\r   [▓▓▓▓▓▓▓▓░░] 80%% Granting Privileges..."
	@sudo mysql -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'localhost';"
	@sudo mysql -e "FLUSH PRIVILEGES;"
	@printf "\r   [▓▓▓▓▓▓▓▓▓▓] 100%% Handshake Complete!               \n"
	@echo "-------------------------------------------------------"
	@echo "✅ SUCCESS: Database '$(DB_NAME)' is live."
	@echo "🔑 ACCESS: User '$(DB_USER)' authenticated with password."
	@echo "-------------------------------------------------------"

setup-web: ## [3/5] Link Nginx config and restart PHP-FPM
	@echo "🌐 Provisioning Native Nginx..."
	@printf "   [▓▓░░░░░░░░] 20%% Setting directory traversal permissions..."
	@sudo chmod +x /home/vboxuser
	@sudo chmod +x /home/vboxuser/Documents
	@sudo chmod +x /home/vboxuser/Documents/SHARPISHLY-THOMASONS
	@sudo chmod -R +r /home/vboxuser/Documents/SHARPISHLY-THOMASONS/web
	@printf "\r   [▓▓▓▓░░░░░░] 40%% Linking Nginx configuration..."
	@sudo cp ./infra/nginx/default.conf /etc/nginx/sites-available/sharpishly
	@sudo ln -sf /etc/nginx/sites-available/sharpishly /etc/nginx/sites-enabled/
	@sudo rm -f /etc/nginx/sites-enabled/default
	@printf "\r   [▓▓▓▓▓▓░░░░] 60%% Testing Nginx syntax..."
	@sudo nginx -t > /dev/null 2>&1
	@printf "\r   [▓▓▓▓▓▓▓▓░░] 80%% Restarting Nginx & PHP-FPM..."
	@sudo systemctl restart nginx
	@sudo systemctl restart php8.2-fpm
	@printf "\r   [▓▓▓▓▓▓▓▓▓▓] 100%% Web Server Ready!               \n"
	@echo "-------------------------------------------------------"
	@echo "✅ SUCCESS: SPA live at http://localhost/"
	@echo "🔗 API: http://localhost/api/health"
	@echo "📂 ROOT: /home/vboxuser/Documents/SHARPISHLY-THOMASONS"
	@echo "-------------------------------------------------------"

setup-python: ## [4/5] Setup Python VirtualEnv and install requirements
	@cd ~/Documents/SHARPISHLY-THOMASONS && python3 -m venv venv
	@~/Documents/SHARPISHLY-THOMASONS/venv/bin/pip install --upgrade pip
	@~/Documents/SHARPISHLY-THOMASONS/venv/bin/pip install --progress-bar pretty -r ~/Documents/SHARPISHLY-THOMASONS/requirements.txt

all: purge-docker install setup-samba setup-db setup-python setup-web ## Execute the entire provisioning flow

