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
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

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

setup-db: create-env ## [2/5] Initialize MariaDB database and user
	@sudo systemctl start mariadb
	@sudo mysql -e "CREATE DATABASE IF NOT EXISTS $(DB_NAME);"
	@sudo mysql -e "CREATE USER IF NOT EXISTS '$(DB_USER)'@'localhost' IDENTIFIED BY '$(DB_PASS)';"
	@sudo mysql -e "GRANT ALL PRIVILEGES ON $(DB_NAME).* TO '$(DB_USER)'@'localhost';"
	@sudo mysql -e "FLUSH PRIVILEGES;"

setup-web: ## [3/5] Link Nginx config and restart PHP-FPM
	@sudo cp ~/Documents/SHARPISHLY-THOMASONS/infra/nginx-native.conf /etc/nginx/sites-available/sharpishly || echo "Config missing!"
	@sudo ln -sf /etc/nginx/sites-available/sharpishly /etc/nginx/sites-enabled/
	@sudo rm -f /etc/nginx/sites-enabled/default
	@sudo systemctl restart nginx
	@sudo systemctl restart php8.2-fpm

setup-python: ## [4/5] Setup Python VirtualEnv and install requirements
	@cd ~/Documents/SHARPISHLY-THOMASONS && python3 -m venv venv
	@~/Documents/SHARPISHLY-THOMASONS/venv/bin/pip install --upgrade pip
	@~/Documents/SHARPISHLY-THOMASONS/venv/bin/pip install --progress-bar pretty -r ~/Documents/SHARPISHLY-THOMASONS/requirements.txt

all: purge-docker install setup-samba setup-db setup-python setup-web ## Execute the entire provisioning flow
