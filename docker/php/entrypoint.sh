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

    # Testdaten nur bei leerer Datenbank anlegen, damit Neustarts keine Duplikate erzeugen
    book_count=$(php bin/console dbal:run-sql "SELECT COUNT(*) AS anzahl FROM book" | tr -dc '0-9')
    if [ "${book_count:-0}" = "0" ]; then
        echo "Leere Datenbank, lege Testdaten an..."
        php bin/console app:create-dummy-data --no-interaction
    fi
fi

exec docker-php-entrypoint "$@"
