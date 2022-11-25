ARG PHP_VERSION=8.0
ARG COMPOSER_VERSION=2.0

FROM composer:${COMPOSER_VERSION}
FROM php:${PHP_VERSION}-cli

# System deps
RUN apt-get update && \
    apt-get install -y autoconf pkg-config libssl-dev git libzip-dev zlib1g-dev protobuf-compiler libboost-all-dev

# PHP deps
RUN pecl install mysql_xdevapi && docker-php-ext-enable mysql_xdevapi
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN docker-php-ext-install -j$(nproc) pdo_mysql zip

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /code