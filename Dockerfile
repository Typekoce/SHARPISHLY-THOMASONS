FROM php:8.2-fpm

# 1. Essential System Dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. PHP Extensions (The DB Bridge)
RUN docker-php-ext-install pdo pdo_mysql

# 3. Composer (Keep it for GitHub Actions/CI)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# 4. Storage & Log Orchestration
# We ensure the structure exists before the first request hits
RUN mkdir -p /var/www/html/storage/logs \
             /var/www/html/storage/uploads \
             /var/www/html/storage/framework/views && \
    touch /var/www/html/storage/logs/app.log && \
    chown -R www-data:www-data /var/www/html/storage && \
    chmod -R 775 /var/www/html/storage

# 5. The "Manifesto" Touch: Ensure the app can always write to its logs
RUN chmod 664 /var/www/html/storage/logs/app.log

# We stay as root for the entrypoint, but PHP-FPM handles the user logic