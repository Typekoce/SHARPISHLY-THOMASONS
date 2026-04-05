FROM php:8.2-fpm-alpine

# 1. Install System Dependencies & Build Tools
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    oniguruma-dev \
    icu-dev \
    mariadb-client \
    netcat-openbsd

# 2. Configure & Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        bcmath

# 3. Install Redis (PECL) - Critical for QueueService
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# 4. Set Environment & Workspace
WORKDIR /var/www/html

# 5. Copy Source & Setup Permissions
COPY . .

# Ensure the Thomasons V3 storage structure is pre-initialized
RUN mkdir -p /var/www/html/storage/uploads \
             /var/www/html/storage/log \
             /var/www/html/storage/database \
             /var/www/html/storage/queue \
    && chown -R www-data:www-data /var/www/html

# 6. Expose FPM port
EXPOSE 9000

CMD ["php-fpm"]