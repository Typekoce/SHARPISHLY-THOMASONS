FROM php:8.2-fpm

# 1. Install System Dependencies & Hardware Tools
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    default-jre \
    usbutils \
    iproute2 \
    util-linux \
    pciutils \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install PHP Extensions
RUN docker-php-ext-install pdo pdo_mysql

# 3. Composer Integration
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 4. Set working directory
WORKDIR /var/www/html

# 5. Infrastructure & Permission Setup
# We create the entire storage tree and the vector DB path in one shot.
# Then we hand the keys to 'www-data' (the PHP user).
RUN mkdir -p /var/www/html/storage/database \
             /var/www/html/storage/logs \
             /var/www/html/storage/uploads \
             /var/www/html/llm/foozie-vector-db/data && \
    chown -R www-data:www-data /var/www/html/storage \
                               /var/www/html/llm/foozie-vector-db/data && \
    chmod -R 775 /var/www/html/storage \
                 /var/www/html/llm/foozie-vector-db/data

# 6. Ensure the PHP process runs as the correct user 
# (This matches the permissions set above)
USER www-data