# ===================== NGINX SITE CONFIG & SSL (DOMAIN-SPECIFIC) =====================
NGINX_AVAIL="/etc/nginx/sites-available/${DOMAIN}"
NGINX_ENABLE="/etc/nginx/sites-enabled/${DOMAIN}"
SSL_CERT="/etc/nginx/ssl/${DOMAIN}.crt"
SSL_KEY="/etc/nginx/ssl/${DOMAIN}.key"
FRONTEND_ROOT="${ROOT_DIR}/web/frontend"

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

echo "Creating Nginx configuration for ${DOMAIN}..."
sudo cat <<EOF | sudo tee "$NGINX_AVAIL" > /dev/null
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} localhost 127.0.0.1;
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name ${DOMAIN} localhost 127.0.0.1;

    client_max_body_size 64M;

    ssl_certificate ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Primary PHP root
    root ${WEB_ROOT};
    index index.php index.html;

    # 1. Root / serves index.html from frontend
    location = / {
        root ${FRONTEND_ROOT};
        try_files /index.html =404;
    }

    # 2. Static assets (CSS, JS, images, fonts, etc.)
    location ~* \.(css|js|ico|png|jpg|jpeg|svg|woff|woff2|ttf|eot)\$ {
        root ${FRONTEND_ROOT};
        try_files \$uri =404;
    }

    # 3. SPA templates/views under /views/
    location /views/ {
        root ${FRONTEND_ROOT};
        try_files \$uri =404;
    }

    # 4. General fallback -> PHP front controller
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # 5. PHP-FPM execution engine
    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME \$fastcgi_script_name;
        fastcgi_param REQUEST_URI \$request_uri;
    }
}
EOF

sudo ln -sf "$NGINX_AVAIL" "$NGINX_ENABLE"
sudo nginx -t && sudo systemctl reload nginx