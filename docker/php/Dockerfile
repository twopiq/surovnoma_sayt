FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    libpq-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-client \
    unzip \
    zip \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

CMD ["php-fpm"]
