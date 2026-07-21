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