FROM composer:2 AS builder

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

RUN curl -sS https://webi.sh/caddy | sh

WORKDIR /app

COPY --from=builder /app /app

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
EXPOSE 80

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
