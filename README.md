# kyberna-assestment

## Entwicklung

Container starten:

```bash
docker compose up -d
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

```bash
bin/console app:create-dummy-Books        # 50 Bücher
bin/console app:create-dummy-Books 200    # beliebige Anzahl
bin/console app:create-dummy-members      # 50 Mitglieder
bin/console app:create-dummy-members 200  # beliebige Anzahl
```

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
