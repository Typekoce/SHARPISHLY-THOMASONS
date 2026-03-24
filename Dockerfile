FROM php:8.2-fpm

# 1. Install System Dependencies & Hardware Tools
# We combine these to reduce the number of image layers
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

# 3. Composer Integration (Optional, but ready if GitHub Actions needs it)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 4. Set working directory
WORKDIR /var/www/html

# 5. Final Permission Check for the Java Bridge
# Ensures PHP can write to the Java data directory
RUN mkdir -p /var/www/html/llm/foozie-vector-db/data && \
    chmod -R 775 /var/www/html/llm/foozie-vector-db/data