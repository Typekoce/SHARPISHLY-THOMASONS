#!/bin/bash
set -euo pipefail

# Clear terminal
clear

# ===================== UTILITIES =====================
string_replace() {
    local search="$1"
    local replace="$2"
    local file="$3"
    if [[ -f "$file" ]]; then
        sudo sed -i "s|${search}|${replace}|g" "$file"
        echo "    ✅ Grounded ${search} in ${file}"
    fi
}

if_exists_remove() {
    local file_name="$1"
    local avail_path="/etc/nginx/sites-available/${file_name}"
    local enable_path="/etc/nginx/sites-enabled/${file_name}"
    if [ -f "$avail_path" ] || [ -L "$enable_path" ]; then
        echo "Removing conflicting Nginx config: ${file_name}..."
        sudo rm -f "$avail_path"
        sudo rm -f "$enable_path"
    fi
}

# ===================== DEFAULT CONFIG & VARIABLES =====================
DB_HOST="localhost"
DB_USER="sharpishly"
DB_PASS="sharpishly"
DB_NAME="sharpishly"
MYSQL_ROOT_PASS=""

DOMAIN="${DOMAIN:-sharpishly.dev}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEB_ROOT="${ROOT_DIR}/web/php/src"
PHP_VERSION="8.3"
STORAGE_PATH="${ROOT_DIR}/storage"
VENV="${ROOT_DIR}/venv"
CURRENT_USER="$(whoami)"

# ===================== PARSE ARGUMENTS =====================
while getopts ":H:u:p:d:r:D:w:v:" opt; do
  case "${opt}" in
    H) DB_HOST="${OPTARG}" ;;
    u) DB_USER="${OPTARG}" ;;
    p) DB_PASS="${OPTARG}" ;;
    d) DB_NAME="${OPTARG}" ;;
    r) MYSQL_ROOT_PASS="${OPTARG}" ;;
    D) DOMAIN="${OPTARG}" ;;
    w) WEB_ROOT="${OPTARG}" ;;
    v) PHP_VERSION="${OPTARG}" ;;
    *) echo "Usage: $0 [-H host] [-u user] [-p pass] [-d db] [-r rootpass] [-D domain] [-w webroot] [-v phpversion]"; exit 1 ;;
  esac
done

# ===================== PRE-INSTALLATION CLEAN-UP =====================
echo -e "\n=== Pre-installation Cleanup ==="
if_exists_remove "default"
if_exists_remove "${DOMAIN}"

# ===================== SYSTEM SETUP =====================
echo -e "\n=== Installing System Dependencies ==="
sudo apt-get update -qq
sudo apt-get install -y ca-certificates apt-transport-https lsb-release gnupg curl nginx mariadb-server php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip python3-venv python3-pip

# ===================== MYSQL SETUP =====================
echo -e "\n=== Configuring MariaDB ==="
MYSQL_CMD=(sudo mysql)
[ -n "$MYSQL_ROOT_PASS" ] && MYSQL_CMD=(mysql -uroot -p"${MYSQL_ROOT_PASS}")

"${MYSQL_CMD[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';
FLUSH PRIVILEGES;
SQL

# ===================== CREATE env.php =====================
if [ ! -f "env.php" ]; then
    echo -e "\n=== Creating env.php ==="
    cat > env.php <<'PHP_EOF'
<?php
define('DB_HOST', '{{DB_HOST}}');
define('DB_USER', '{{DB_USER}}');
define('DB_PASS', '{{DB_PASS}}');
define('DB_NAME', '{{DB_NAME}}');
PHP_EOF
    string_replace "{{DB_HOST}}" "${DB_HOST}" "env.php"
    string_replace "{{DB_USER}}" "${DB_USER}" "env.php"
    string_replace "{{DB_PASS}}" "${DB_PASS}" "env.php"
    string_replace "{{DB_NAME}}" "${DB_NAME}" "env.php"
fi

# ===================== WEB STORAGE & PERMISSIONS =====================
mkdir -p "${STORAGE_PATH}"/{logs,vectors,uploads/nats/{ingest,process,archive,fail}}
sudo chown -R "${CURRENT_USER}:www-data" "${STORAGE_PATH}"
sudo chmod -R 2775 "${STORAGE_PATH}"

# ===================== PYTHON LIBRARIES =====================
echo -e "\n=== Installing Python Dependencies ==="
[ ! -d "$VENV" ] && python3 -m venv "$VENV"
"$VENV/bin/pip" install --upgrade pip --quiet
# Added 'requests' for LLM generation connectivity
"$VENV/bin/pip" install requests chromadb ollama watchdog --quiet

# ===================== OLLAMA SETUP =====================
echo -e "\n=== Setting up Ollama ==="
if ! command -v ollama &>/dev/null; then
  curl -fsSL https://ollama.com/install.sh | sh
fi

if ! pgrep -x ollama >/dev/null; then
  ollama serve >/tmp/ollama.log 2>&1 &
  sleep 5
fi

pull_if_missing() {
  ollama list | grep -Fq "$1" || ollama pull "$1"
}

pull_if_missing "llama3"
pull_if_missing "jina/jina-embeddings-v2-small-en"

echo -e "\n=== Installation Complete ==="
echo -e "Run 'make run-rag' to start the RAG service."

# ===================== PENTEST SETUP =====================
# Script to install security tools on Ubuntu 24.04
# =========================================================

echo "Updating package lists..."
sudo apt-get update -y

echo "Installing core tools..."
sudo apt-get install -y nmap sqlmap

# Metasploit Framework (Official Rapid7 Installer)
if ! command -v msfconsole &> /dev/null; then
    echo "Installing Metasploit..."
    curl https://raw.githubusercontent.com/rapid7/metasploit-omnibus/master/config/install.rb > msfinstall
    sudo ruby msfinstall
    rm msfinstall
else
    echo "Metasploit is already installed."
fi

# Nuclei (Requires Go)
if ! command -v go &> /dev/null; then
    echo "Go not found. Please install Go to use Nuclei."
else
    echo "Installing/Updating Nuclei..."
    go install -v github.com/projectdiscovery/nuclei/v3/cmd/nuclei@latest
fi

echo "Pentest setup complete."

# ===================== SYSTEM SETUP =====================
echo -e "\n=== Installing System Dependencies ==="
sudo apt-get update -qq
# Added default-jdk to ensure javac is available
sudo apt-get install -y ca-certificates apt-transport-https lsb-release gnupg curl nginx mariadb-server php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip python3-venv python3-pip default-jdk


# ===================== HIMALAYA EMAIL CLIENT =====================

# Verify dependencies
if ! command -v curl >/dev/null 2>&1; then
    echo "curl is required but not installed. Aborting."
    exit 1
fi

# Detect distribution and install
if command -v apt-get >/dev/null 2>&1 || command -v dnf >/dev/null 2>&1; then
    echo "Installing Himalaya..."
    curl -sSL https://raw.githubusercontent.com/pimalaya/himalaya/master/install.sh | sudo sh
else
    echo "Unsupported distribution."
    exit 1
fi

mkdir -p ~/.config/himalaya/
touch ~/.config/himalaya/config.toml

# Set directory permissions
chmod 700 ~/.config/himalaya/

# Set file permissions
chmod 600 ~/.config/himalaya/config.toml

echo "Himalaya installed successfully."
echo "Run 'himalaya --help' to verify the installation."

# ===================== SSH SET-UP REMOTE MACHINE @maxie =====================


# Define the config block
CONFIG_ENTRY="
Host maxie
    HostName 192.168.0.90
    User maxie
    ConnectTimeout 5
    ServerAliveInterval 60
    IdentitiesOnly yes
    # IdentityFile ~/.ssh/id_ed25519_maxie
"

SSH_CONFIG="$HOME/.ssh/config"

# Ensure ~/.ssh directory exists
mkdir -p "$HOME/.ssh"
touch "$SSH_CONFIG"

# Check if 'Host maxie' already exists to avoid duplicates
if grep -q "Host maxie" "$SSH_CONFIG"; then
    echo "Configuration for 'maxie' already exists in $SSH_CONFIG."
else
    # Append the configuration
    echo "$CONFIG_ENTRY" >> "$SSH_CONFIG"
    # Ensure correct permissions for SSH config
    chmod 600 "$SSH_CONFIG"
    echo "Successfully added 'maxie' configuration to $SSH_CONFIG."
fi

# ===================== CLI TOOLS =====================

# Ensure we have sudo rights
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root (sudo)." >&2
    exit 1
fi

# Configuration
TOOLS=("tmux" "vim" "zsh" "git" "htop" "curl" "wget" "pass" "jq" "ripgrep" "fzf" "mtr" "nmap" "tree" "mariadb-client")
export DEBIAN_FRONTEND=noninteractive

# 1. System Update and Installation
echo "--- Updating and installing core tools ---"
apt-get update -yq
apt-get install -yq "${TOOLS[@]}"

# 2. SSH Configuration Bootstrap
echo "--- Configuring SSH aliases ---"
mkdir -p "$HOME/.ssh"
cat <<EOF >> "$HOME/.ssh/config"

# Project Environment Aliases
Host joe90
    HostName 192.168.1.50
    User dev
Host foozie
    HostName 192.168.1.51
    User dev
Host tardis
    HostName 192.168.1.52
    User dev
EOF
chmod 600 "$HOME/.ssh/config"

# 3. MariaDB Health-Check Utility
echo "--- Installing health-check utility ---"
cat <<'EOF' > /usr/local/bin/db-check
#!/usr/bin/env bash
if mysqladmin ping -h localhost --silent; then
    echo "MariaDB is UP."
    exit 0
else
    echo "MariaDB is DOWN."
    exit 1
fi
EOF
chmod +x /usr/local/bin/db-check

echo "--- Setup Complete ---"
echo "Installed: ${TOOLS[*]}"
echo "Tip: Run 'db-check' to verify your MariaDB status."