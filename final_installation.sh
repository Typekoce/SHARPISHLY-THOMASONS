#!/usr/bin/env bash
# Auto-generated consolidated installation script
# Generated on Fri 24 Jul 14:10:11 BST 2026

set -euo pipefail


# --- Start of 1_utilities.sh ---

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
# --- End of 1_utilities.sh ---

# --- Start of 2_config.sh ---
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
# --- End of 2_config.sh ---

# --- Start of 3_parse_arguements.sh ---
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
# --- End of 3_parse_arguements.sh ---

# --- Start of 4_system_dependencies.sh ---
# ===================== SYSTEM DEPENDENCIES =====================
echo -e "\n=== Installing System Dependencies ==="
export DEBIAN_FRONTEND=noninteractive
CLI_TOOLS=("tmux" "vim" "zsh" "git" "htop" "curl" "wget" "pass" "jq" "ripgrep" "fzf" "mtr" "nmap" "tree" "mariadb-client" "sqlmap")

sudo apt-get update -qq
sudo apt-get install -yq \
  ca-certificates apt-transport-https lsb-release gnupg curl nginx mariadb-server default-jdk \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip \
  python3-venv python3-pip "${CLI_TOOLS[@]}"

sudo systemctl enable --now "php${PHP_VERSION}-fpm"
# --- End of 4_system_dependencies.sh ---

# --- Start of 5_mariadb.sh ---
# ===================== MARIADB SETUP =====================
echo -e "\n=== Configuring MariaDB ==="
MYSQL_CMD=(sudo mysql)
[ -n "$MYSQL_ROOT_PASS" ] && MYSQL_CMD=(mysql -uroot -p"${MYSQL_ROOT_PASS}")

"${MYSQL_CMD[@]}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';
FLUSH PRIVILEGES;
SQL
# --- End of 5_mariadb.sh ---

# --- Start of 6_env_php.sh ---
# ===================== CREATE env.php (ATOMIC & PROTECTED) =====================
ENV_FILE="${ROOT_DIR}/env.php"
if [ ! -f "$ENV_FILE" ]; then
    echo -e "\n=== Creating env.php ==="
    TMP_ENV=$(mktemp)
    cat <<EOF > "$TMP_ENV"
<?php
define('DB_HOST', '${DB_HOST}');
define('DB_USER', '${DB_USER}');
define('DB_PASS', '${DB_PASS}');
define('DB_NAME', '${DB_NAME}');
EOF
    mv "$TMP_ENV" "$ENV_FILE"
    chmod 600 "$ENV_FILE"
    echo "    ✅ env.php created with permissions 0600."
else
    echo -e "\n=== Skipping env.php creation (file already exists) ==="
    chmod 600 "$ENV_FILE"
fi
# --- End of 6_env_php.sh ---

# --- Start of 7_nginx.sh ---

# ===================== NGINX SITE CONFIG & SSL (DOMAIN-SPECIFIC) =====================
NGINX_AVAIL="/etc/nginx/sites-available/${DOMAIN}"
NGINX_ENABLE="/etc/nginx/sites-enabled/${DOMAIN}"
SSL_CERT="/etc/nginx/ssl/${DOMAIN}.crt"
SSL_KEY="/etc/nginx/ssl/${DOMAIN}.key"

# Remove default site if still enabled
if [ -f "/etc/nginx/sites-available/default" ] || [ -L "/etc/nginx/sites-enabled/default" ]; then
    echo "Removing default Nginx config..."
    sudo rm -f "/etc/nginx/sites-available/default" "/etc/nginx/sites-enabled/default"
fi

echo -e "\n=== Configuring SSL & Nginx Site (${DOMAIN}) ==="
sudo mkdir -p /etc/nginx/ssl

if [ ! -f "$SSL_CERT" ] || [ ! -f "$SSL_KEY" ]; then
    echo "Generating Domain-Specific SSL Certificate for ${DOMAIN}..."
    sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
      -keyout "$SSL_KEY" \
      -out "$SSL_CERT" \
      -subj "/CN=${DOMAIN}/O=Sharpishly" \
      -addext "subjectAltName=DNS:${DOMAIN},DNS:*.${DOMAIN}"
    sudo chmod 600 "$SSL_KEY"
    sudo chmod 644 "$SSL_CERT"
fi

if [ ! -f "$NGINX_AVAIL" ]; then
    echo "Creating Nginx configuration for ${DOMAIN}..."
    sudo cat <<EOF | sudo tee "$NGINX_AVAIL" > /dev/null
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name ${DOMAIN};

    client_max_body_size 64M;

    ssl_certificate ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root ${WEB_ROOT};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }
}
EOF
    sudo ln -sf "$NGINX_AVAIL" "$NGINX_ENABLE"
    sudo nginx -t && sudo systemctl reload nginx
else
    echo "Skipping Nginx config creation (site '${DOMAIN}' already exists)."
fi


# --- End of 7_nginx.sh ---

# --- Start of 8_web_storage_permissions.sh ---
# ===================== WEB STORAGE & PERMISSIONS =====================
echo -e "\n=== Preparing Storage Directories ==="
mkdir -p "${STORAGE_PATH}"/{logs,vectors,uploads/queue/{ingest,process,archive,fail}}
sudo chown -R "${CURRENT_USER}:www-data" "${STORAGE_PATH}"
sudo chmod -R 2775 "${STORAGE_PATH}"
# --- End of 8_web_storage_permissions.sh ---

# --- Start of 9_python_libraries.sh ---
# ===================== PYTHON LIBRARIES =====================
echo -e "\n=== Installing Python Dependencies ==="
[ ! -d "$VENV" ] && python3 -m venv "$VENV"
"$VENV/bin/pip" install --upgrade pip --quiet
"$VENV/bin/pip" install requests chromadb ollama watchdog --quiet
# --- End of 9_python_libraries.sh ---

# --- Start of 10_ollama_setup.sh ---
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
# --- End of 10_ollama_setup.sh ---

# --- Start of 11_pentest_setup.sh ---
# ===================== PENTEST SETUP =====================
echo -e "\n=== Setting up Pentest Tools ==="
if ! command -v msfconsole &> /dev/null; then
    echo "Installing Metasploit..."
    curl https://raw.githubusercontent.com/rapid7/metasploit-omnibus/master/config/install.rb > msfinstall
    sudo ruby msfinstall
    rm -f msfinstall
else
    echo "Metasploit is already installed."
fi

if command -v go &> /dev/null; then
    if ! command -v nuclei &> /dev/null; then
        echo "Installing Nuclei..."
        go install -v github.com/projectdiscovery/nuclei/v3/cmd/nuclei@latest
    fi
fi
# --- End of 11_pentest_setup.sh ---

# --- Start of 12_himalaya_email_client.sh ---
# ===================== HIMALAYA EMAIL CLIENT =====================
echo -e "\n=== Configuring Himalaya Email Client ==="
if ! command -v himalaya &>/dev/null; then
    curl -sSL https://raw.githubusercontent.com/pimalaya/himalaya/master/install.sh | sudo sh
fi

mkdir -p "$HOME/.config/himalaya"
chmod 700 "$HOME/.config/himalaya"

if [ ! -f "$HOME/.config/himalaya/config.toml" ]; then
    touch "$HOME/.config/himalaya/config.toml"
fi
chmod 600 "$HOME/.config/himalaya/config.toml"
# --- End of 12_himalaya_email_client.sh ---

# --- Start of 13_ssh_config.sh ---
# ===================== SSH CONFIGURATION (INDIVIDUAL ANCHORED GUARDS) =====================
echo -e "\n=== Configuring SSH Aliases ==="
SSH_CONFIG="$HOME/.ssh/config"
mkdir -p "$HOME/.ssh"
touch "$SSH_CONFIG"
chmod 600 "$SSH_CONFIG"

add_ssh_host() {
    local host_alias="$1"
    local hostname="$2"
    local user="$3"

    if ! grep -qE "^Host[[:space:]]+${host_alias}\$" "$SSH_CONFIG"; then
        cat <<EOF >> "$SSH_CONFIG"

Host ${host_alias}
    HostName ${hostname}
    User ${user}
EOF
        echo "    ✅ Added SSH host '${host_alias}' (${hostname})."
    else
        echo "    ⏩ SSH host '${host_alias}' already exists."
    fi
}

add_ssh_host "maxie" "192.168.0.90" "maxie"
add_ssh_host "joe90" "192.168.1.50" "dev"
add_ssh_host "foozie" "192.168.1.51" "dev"
add_ssh_host "tardis" "192.168.1.52" "dev"
# --- End of 13_ssh_config.sh ---

# --- Start of 14_health_check.sh ---
# ===================== HEALTH-CHECK UTILITY =====================
echo -e "\n=== Installing Health-Check Utility ==="
if [ ! -f /usr/local/bin/db-check ]; then
    cat <<'EOF' | sudo tee /usr/local/bin/db-check > /dev/null
if mysqladmin ping -h localhost --silent; then
    echo "MariaDB is UP."
    exit 0
else
    echo "MariaDB is DOWN."
    exit 1
fi
EOF
    sudo chmod +x /usr/local/bin/db-check
fi

echo -e "\n=== Installation Complete ==="
echo "Tip: Run 'db-check' to verify your MariaDB status."
# --- End of 14_health_check.sh ---
