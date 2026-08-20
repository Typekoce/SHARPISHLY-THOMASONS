# ===================== DEFAULT CONFIG & VARIABLES =====================
DB_HOST="localhost"
DB_USER="sharpishly"
DB_PASS="sharpishly"
DB_NAME="sharpishly"
MYSQL_ROOT_PASS=""

DOMAIN="${DOMAIN:-sharpishly.dev}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEB_ROOT="${ROOT_DIR}/web/php/src"
STORAGE_PATH="${ROOT_DIR}/storage"
VENV="${ROOT_DIR}/venv"
CURRENT_USER="$(whoami)"

# Shared Runtime Environment File
RUNTIME_ENV="/run/myapp-runtime.env"

# Array definition for CLI utilities
CLI_TOOLS=(
  "tmux" "vim" "zsh" "git" "htop" "curl" "wget" 
  "pass" "jq" "ripgrep" "fzf" "mtr" "nmap" "tree" 
  "mariadb-client" "sqlmap"
)