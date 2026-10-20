# API

Basis-URL: http://localhost:8080/api (Doku/Swagger UI unter derselben Adresse).

| Methode | Pfad | Beschreibung |
|---|---|---|
| `GET` | `/api/books` | alle Bücher, filterbar nach Autor |
| `GET` | `/api/loans` | alle Ausleihen |
| `POST` | `/api/loans` | Buch ausleihen |
| `POST` | `/api/loans/{id}/return` | Ausleihe zurückgeben |

## Bücher (`GET /api/books`)

Filter nach Autor, Teilstring, Groß-/Kleinschreibung egal (`QueryParameter` mit `PartialSearchFilter`):

```bash
curl 'http://localhost:8080/api/books?author=weber'
curl 'http://localhost:8080/api/books?author[]=weber&author[]=koch'   # mehrere Werte, ODER-verknüpft
```

## Ausleihen (`POST /api/loans`)

Beim Anlegen einer Ausleihe greift der `App\State\LoanProcessor` → `LoanService::prepareLoan()` und setzt folgende Werte automatisch (vom Client gesendete Werte werden überschrieben):

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
