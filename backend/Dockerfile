FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zlib1g-dev \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    curl \
    zip \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

# Enable OPcache for faster PHP execution
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Install composer from official composer image
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Pre-install composer deps at build time (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts --no-dev || true

COPY . .

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

CMD ["docker-entrypoint.sh"]

