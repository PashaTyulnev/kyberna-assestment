# kyberna-assestment

Ausführliche Dokumentation im Ordner [`docs/`](docs/):
[Setup](docs/setup.md) · [API](docs/api.md) · [Dummy-Daten](docs/dummy-data.md) · [Erinnerungen & Cronjob](docs/reminders.md) · [Tests](docs/testing.md) · [Code-Style](docs/code-style.md)

## Docker

```bash
docker compose up -d --build                           # starten (Migrationen + Testdaten bei leerer DB)
docker compose down -v && docker compose up -d --build # komplett neu aufsetzen, löscht alle Daten
make sh                                                # Shell im PHP-Container
```

## Console (im Container, `make sh`)

```bash
bin/console app:create-dummy-data                                      # Testdaten anlegen
bin/console app:create-dummy-data --books=200 --members=100 --loans=80
bin/console app:overdue-loans-reminder                                 # Erinnerungen abgleichen
bin/console make:migration                                             # Migration erzeugen
bin/console doctrine:migrations:migrate                                # Migrationen ausführen
```

## Cronjob (auf dem Host, `crontab -e`)

```cron
0 6 * * * cd /pfad/zum/projekt && docker compose exec -T php bin/console app:overdue-loans-reminder >> var/log/overdue-reminders.log 2>&1
```

## API

```bash
curl 'http://localhost:8080/api/books?author=weber'
curl -X POST http://localhost:8080/api/loans -H 'Content-Type: application/ld+json' \
  -d '{"book": "/api/books/1", "member": "/api/members/1"}'
curl -X POST http://localhost:8080/api/loans/1/return
```

## Tests & Code-Style (im Container)

```bash
php bin/phpunit                                # alle Tests
php bin/phpunit --filter LoanServiceTest       # einzelne Klasse
vendor/bin/php-cs-fixer fix --dry-run --diff   # Code-Style prüfen
vendor/bin/php-cs-fixer fix                    # Code-Style korrigieren
```
