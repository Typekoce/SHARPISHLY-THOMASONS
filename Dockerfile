FROM php:8.2-fpm

# 1. Essential System Dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. PHP Extensions (The DB & Queue Bridge)
# We add 'redis' via PECL and 'zip' for composer/framework support
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install pdo pdo_mysql zip

# 3. Composer (Keep it for GitHub Actions/CI)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# 4. Storage & Log Orchestration
RUN mkdir -p /var/www/html/storage/logs \
             /var/www/html/storage/uploads \
             /var/www/html/storage/framework/views && \
    touch /var/www/html/storage/logs/app.log && \
    chown -R www-data:www-data /var/www/html/storage && \
    chmod -R 775 /var/www/html/storage

# 5. The "Manifesto" Touch
RUN chmod 664 /var/www/html/storage/logs/app.log

# PHP-FPM will run as www-data via its internal config