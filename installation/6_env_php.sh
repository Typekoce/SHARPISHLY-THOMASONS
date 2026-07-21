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