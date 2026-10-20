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

Der Client schickt nur die IDs von Buch und Mitglied:

```bash
curl -X POST http://localhost:8080/api/loans \
  -H 'Content-Type: application/ld+json' \
  -d '{"book_id": 1, "member_id": 1}'
```

| Feld | Typ | Regel |
|---|---|---|
| `book_id` | int | Pflicht, positiv |
| `member_id` | int | Pflicht, positiv |

Weitere Felder im Body (z. B. `loanedAt`) werden ignoriert. IRIs wie `"book": "/api/books/1"` werden nicht angenommen.

### Ablauf

1. API Platform liest den Body in das DTO `App\Dto\CreateLoanRequest` ein (`input:` an der Post-Operation in `App\Entity\Loan`) und validiert es (`#[Assert\NotNull]`, `#[Assert\Positive]`).
2. `App\State\LoanProcessor` lädt Buch und Mitglied über `BookRepository` und `MemberRepository` und erzeugt die Ausleihe.
3. `LoanService::prepareLoan()` prüft die verfügbaren Kopien, reduziert `availableCopies` um 1 und setzt die Daten:

   | Feld         | Wert                                                      |
   |--------------|-----------------------------------------------------------|
   | `loanedAt`   | aktueller Zeitpunkt                                       |
   | `dueAt`      | `loanedAt` + `Loan::LOAN_PERIOD` (`P2W`, also 2 Wochen)   |
   | `returnedAt` | `null`                                                    |

4. Der Standard-Processor von API Platform speichert die Ausleihe. Die Antwort ist die angelegte Ausleihe, Verknüpfungen darin als IRI (`"book": "/api/books/1"`).

### Antworten

| Status | Wann |
|---|---|
| `201 Created` | Ausleihe angelegt |
| `400 Bad Request` | falscher Typ, z. B. `"book_id": "abc"` |
| `404 Not Found` | Buch oder Mitglied mit der ID existiert nicht |
| `409 Conflict` | keine Kopie verfügbar (`availableCopies` = 0) |
| `422 Unprocessable Entity` | `book_id` / `member_id` fehlt oder ist nicht positiv |

## Rückgabe (`POST /api/loans/{id}/return`)

Markiert eine Ausleihe als zurückgegeben (`App\State\ReturnLoanProcessor` → `LoanService::returnLoan()`):

- `returnedAt` wird auf den aktuellen Zeitpunkt gesetzt
- `availableCopies` des Buchs wird um 1 erhöht
- bereits zurückgegeben → `409 Conflict`, unbekannte ID → `404 Not Found`

Kein Request-Body nötig:

```bash
curl -X POST http://localhost:8080/api/loans/1/return
```
