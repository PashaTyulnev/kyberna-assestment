# kyberna-assestment

## Entwicklung

Container starten:

```bash
docker compose up -d --build
```

Beim Start des `php`-Containers (`docker/php/entrypoint.sh`) passiert automatisch:

1. `composer install`, falls `vendor/` fehlt
2. warten, bis die Datenbank erreichbar ist
3. alle Migrationen ausführen
4. **nur bei leerer Datenbank** (keine Bücher vorhanden) Testdaten anlegen (`app:create-dummy-data`): 50 Bücher, 50 Mitglieder, 30 Ausleihen und die Erinnerungen für die überfälligen davon

Danach ist die App direkt mit Daten unter http://localhost:8080/api erreichbar. Neustarts legen keine weiteren Testdaten an.

`--build` ist nötig, wenn sich `docker/php/entrypoint.sh` oder das Dockerfile geändert haben, weil beide ins Image kopiert werden.

Komplett neu aufsetzen (**löscht alle Daten** der lokalen Datenbank):

```bash
docker compose down -v && docker compose up -d --build
```

In den PHP-Container wechseln (Bash-Shell im Projektverzeichnis `/var/www/html`):

```bash
make sh
```

Voraussetzung: Die Container laufen bereits (`docker compose up -d`).

## Maker Bundle & Migrationen

Im Container (`make sh`):

```bash
bin/console make:entity Foo                # Entity anlegen/erweitern
bin/console make:migration                 # Migration aus Entity-Änderungen erzeugen
bin/console doctrine:migrations:migrate    # Migrationen ausführen
```



## Dummy-Daten

Ein Command legt zusammenhängende Testdaten an: Bücher, Mitglieder, Ausleihen und die Erinnerungen für die überfälligen Ausleihen.

```bash
bin/console app:create-dummy-data                                    # 50 Bücher, 50 Mitglieder, 30 Ausleihen
bin/console app:create-dummy-data --books=200 --members=100 --loans=80
```

- die Ausleihen liegen in den letzten 30 Tagen, ein Teil ist also überfällig (Leihfrist 2 Wochen)
- rund 30 % sind bereits zurückgegeben
- offene Ausleihen reduzieren `availableCopies`, Bücher ohne verfügbare Kopien werden dafür nicht verwendet
- für die überfälligen Ausleihen werden Erinnerungen angelegt (wie bei `app:overdue-loans-reminder`)

Die Daten kommen zu vorhandenen dazu. Beim Docker-Start läuft der Command automatisch, aber nur bei leerer Datenbank.

## Ausleihen (`POST /api/loans`)

Beim Anlegen einer Ausleihe greift der `App\State\LoanProcessor` und setzt folgende Werte automatisch (vom Client gesendete Werte werden überschrieben):

| Feld         | Wert                                                      |
|--------------|-----------------------------------------------------------|
| `loanedAt`   | aktueller Zeitpunkt                                       |
| `dueAt`      | `loanedAt` + `Loan::LOAN_PERIOD` (`P2W`, also 2 Wochen)   |
| `returnedAt` | `null`                                                    |

Der Client muss daher nur `book` und `member` mitschicken:

```bash
curl -X POST http://localhost:8080/api/loans \
  -H 'Content-Type: application/ld+json' \
  -d '{"book": "/api/books/1", "member": "/api/members/1"}'
```

Ist keine Kopie mehr verfügbar (`availableCopies` = 0), antwortet die API mit `409 Conflict`. Sonst wird `availableCopies` um 1 reduziert.

## Rückgabe (`POST /api/loans/{id}/return`)

Markiert eine Ausleihe als zurückgegeben (`App\State\ReturnLoanProcessor` → `LoanService::returnLoan()`):

- `returnedAt` wird auf den aktuellen Zeitpunkt gesetzt
- `availableCopies` des Buchs wird um 1 erhöht
- bereits zurückgegeben → `409 Conflict`, unbekannte ID → `404 Not Found`

Kein Request-Body nötig:

```bash
curl -X POST http://localhost:8080/api/loans/1/return
```

## Erinnerungen für überfällige Ausleihen

Der Command `app:overdue-loans-reminder` (`App\Command\OverdueLoansReminderCommand` → `ReminderService::syncOverdueReminders()`) gleicht die Tabelle `reminders` mit den überfälligen Ausleihen ab. Eine echte E-Mail wird nicht verschickt.

- **überfällig** = nicht zurückgegeben (`returnedAt` ist `null`) und `dueAt` liegt in der Vergangenheit
- für jede überfällige Ausleihe gibt es danach genau eine Erinnerung mit dem heutigen Datum (`date`, `overdue_loan_id`)
- entfernt werden Erinnerungen, deren Ausleihe nicht mehr überfällig ist (z. B. zurückgegeben), und Erinnerungen von früheren Tagen
- mehrfaches Ausführen am selben Tag ändert nichts

Manuell ausführen:

```bash
docker compose exec php bin/console app:overdue-loans-reminder   # vom Host
bin/console app:overdue-loans-reminder                           # im Container (make sh)
```

### Täglicher Cronjob

Auf dem Host die Crontab öffnen:

```bash
crontab -e
```

Und folgende Zeile eintragen (täglich um 06:00 Uhr). Den Pfad auf das Projektverzeichnis anpassen; die Container müssen laufen (`docker compose up -d`):

```cron
0 6 * * * cd /pfad/zum/projekt && docker compose exec -T php bin/console app:overdue-loans-reminder >> var/log/overdue-reminders.log 2>&1
```

- `-T` ist nötig, weil cron kein Terminal hat
- die Ausgabe landet in `var/log/overdue-reminders.log`
- ohne Docker (PHP direkt auf dem Server): `0 6 * * * cd /pfad/zum/projekt && php bin/console app:overdue-loans-reminder --env=prod >> var/log/overdue-reminders.log 2>&1`

Prüfen, ob der Eintrag gespeichert ist:

```bash
crontab -l
```

## Tests

Im Container (`make sh`). Es gibt nur Unit-Tests ohne Datenbank, keine Tests greifen auf eine DB zu:

```bash
php bin/phpunit                                   # alle Tests
php bin/phpunit tests/Service                     # Verzeichnis
php bin/phpunit tests/Service/LoanServiceTest.php # einzelne Datei
php bin/phpunit --filter LoanServiceTest          # Klasse
php bin/phpunit --filter testPrepareLoanSetsDefaultDates # Methode
php bin/phpunit --filter 'LoanServiceTest::testPrepareLoanSetsDefaultDates'
```

## Code-Style

`@Symfony`-Regelsatz (PSR-12-basiert) plus `declare(strict_types=1)` für `src/`, `tests/` und `migrations/`, siehe `.php-cs-fixer.dist.php`:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff   # nur prüfen
vendor/bin/php-cs-fixer fix                    # automatisch korrigieren
```

Projektvorgabe: PHPDoc mit Kurzbeschreibung, `@param` und `@return` an jeder Klasse und jeder öffentlichen Methode.
