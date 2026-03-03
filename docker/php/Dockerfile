FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash curl git unzip icu-dev oniguruma-dev \
    libzip-dev linux-headers gmp-dev $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-install \
    pdo pdo_mysql mbstring zip intl opcache gmp bcmath

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
