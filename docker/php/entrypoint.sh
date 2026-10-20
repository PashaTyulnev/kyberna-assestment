#!/usr/bin/env bash
set -euo pipefail

if [ "${1:-}" = "php-fpm" ]; then
    if [ ! -f vendor/autoload.php ]; then
        composer install --prefer-dist --no-progress --no-interaction
    fi

    echo "Waiting for database..."
    until php bin/console dbal:run-sql -q "SELECT 1" > /dev/null 2>&1; do
        sleep 1
    done

    if [ -d migrations ] && ls migrations/*.php > /dev/null 2>&1; then
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    fi
fi

exec docker-php-entrypoint "$@"
