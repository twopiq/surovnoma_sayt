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

WORKDIR /var/www/html

CMD ["php-fpm"]
