# Tests

Es gibt nur Unit-Tests ohne Datenbank (`PHPUnit\Framework\TestCase`). Repositories und EntityManager werden gemockt, die Uhrzeit über `MockClock` festgelegt. Kein Test greift auf eine DB zu.

| Ordner | Inhalt |
|---|---|
| `tests/Service/` | Geschäftslogik (`LoanService`, `ReminderService`) |
| `tests/Command/` | Console-Commands |
| `tests/MessageHandler/` | Messenger-Handler |

Ausführen im Container (`make sh`):

```bash
php bin/phpunit                                   # alle Tests
php bin/phpunit tests/Service                     # Verzeichnis
php bin/phpunit tests/Service/LoanServiceTest.php # einzelne Datei
php bin/phpunit --filter LoanServiceTest          # Klasse
php bin/phpunit --filter testPrepareLoanSetsDefaultDates # Methode
php bin/phpunit --filter 'LoanServiceTest::testPrepareLoanSetsDefaultDates'
```
