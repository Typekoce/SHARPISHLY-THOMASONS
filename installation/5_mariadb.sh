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