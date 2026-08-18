# Dynamically resolve root and parent directories
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WEB_ROOT="${ROOT_DIR}/web/php/src"
STORAGE_PATH="${ROOT_DIR}/storage"
PARENT_DIR="$(dirname "$ROOT_DIR")"
HOME_DIR="$(dirname "$PARENT_DIR")"
CURRENT_USER="$(whoami)"

echo "=== Applying Parent Directory Traversal Permissions ==="
chmod 755 "$HOME_DIR"
chmod 755 "$PARENT_DIR"
chmod 755 "$ROOT_DIR"

echo "=== Setting Web Root Ownership & Modes (${WEB_ROOT}) ==="
if [ -d "$WEB_ROOT" ]; then
    sudo chown -R "${CURRENT_USER}:www-data" "$WEB_ROOT"
    find "$WEB_ROOT" -type d -exec chmod 755 {} \;
    find "$WEB_ROOT" -type f -exec chmod 644 {} \;
else
    echo "Warning: Web root directory does not exist at ${WEB_ROOT}"
fi

echo "=== Setting Storage Directory Permissions (${STORAGE_PATH}) ==="
mkdir -p "${STORAGE_PATH}"/{logs,vectors,uploads/queue/{ingest,process,archive,fail}}
sudo chown -R "${CURRENT_USER}:www-data" "${STORAGE_PATH}"
sudo chmod -R 2775 "${STORAGE_PATH}"

echo "=== Verifying Nginx & Reloading Service ==="
sudo nginx -t
sudo systemctl reload nginx

echo "Post-install setup complete."