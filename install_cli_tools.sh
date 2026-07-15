#!/usr/bin/env bash
set -euo pipefail

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