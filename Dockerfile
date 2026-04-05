FROM php:8.2-fpm-alpine

# 1. Install System Dependencies (Minimal)
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    oniguruma-dev \
    icu-dev

# 2. Install PHP Extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring zip intl bcmath

# 3. Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# 4. Set working directory
WORKDIR /var/www/html

# 5. Copy source code
COPY . .

# 6. Ensure storage structure and permissions
RUN mkdir -p /var/www/html/storage/uploads \
             /var/www/html/storage/log \
             /var/www/html/storage/database \
             /var/www/html/storage/queue \
    && chown -R www-data:www-data /var/www/html/storage

EXPOSE 9000

CMD ["php-fpm"]