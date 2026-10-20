# Setup & Entwicklung

## Container starten

```bash
docker compose up -d --build
```

Beim Start des `php`-Containers (`docker/php/entrypoint.sh`) passiert automatisch:

1. `composer install`, falls `vendor/` fehlt
2. warten, bis die Datenbank erreichbar ist
3. alle Migrationen ausführen
4. **nur bei leerer Datenbank** (keine Bücher vorhanden) Testdaten anlegen mit `app:create-dummy-data`, siehe [Dummy-Daten](dummy-data.md)

Danach ist die App direkt mit Daten unter http://localhost:8080/api erreichbar. Neustarts legen keine weiteren Testdaten an.

`--build` ist nötig, wenn sich `docker/php/entrypoint.sh` oder das Dockerfile geändert haben, weil beide ins Image kopiert werden.

## Komplett neu aufsetzen

**Löscht alle Daten** der lokalen Datenbank:

```bash
docker compose down -v && docker compose up -d --build
```

## In den PHP-Container wechseln

Bash-Shell im Projektverzeichnis `/var/www/html`. Voraussetzung: Die Container laufen bereits.

```bash
make sh
```

## Maker Bundle & Migrationen

Im Container (`make sh`):

```bash
bin/console make:entity Foo                # Entity anlegen/erweitern
bin/console make:migration                 # Migration aus Entity-Änderungen erzeugen
bin/console doctrine:migrations:migrate    # Migrationen ausführen
```

Schemaänderungen laufen immer über Migrationen. Neue Migrationen werden beim nächsten Container-Start automatisch ausgeführt.
