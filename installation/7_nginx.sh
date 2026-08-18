
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
    server_name ${DOMAIN} localhost;
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

