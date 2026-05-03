#!/bin/sh
set -e

echo "Running Symfony post-install tasks..."

touch .env

php bin/console assets:install --env=prod
php bin/console importmap:install --env=prod
php bin/console asset-map:compile --env=prod

php bin/console doctrine:migrations:migrate --no-interaction

echo "Starting PHP-FPM and Caddy server..."

export PATH=$PATH:/root/.local/bin # include caddy bin

chown -R nobody:nobody /app

php-fpm &
caddy run --config /etc/caddy/Caddyfile
