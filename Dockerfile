FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git curl libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/storage/{database,uploads,logs} \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage