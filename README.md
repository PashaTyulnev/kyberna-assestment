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

## Tests

Im Container (`make sh`), laufen gegen die normale Datenbank `app`:

```bash
php bin/phpunit                                   # alle Tests
php bin/phpunit tests/Api                         # Verzeichnis
php bin/phpunit tests/Api/BookApiTest.php         # einzelne Datei
php bin/phpunit --filter BookApiTest              # Klasse
php bin/phpunit --filter testGetCollectionReturnsArray # Methode
php bin/phpunit --filter 'BookApiTest::testGetCollectionReturnsArray'
```
