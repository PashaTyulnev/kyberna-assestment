# Erinnerungen für überfällige Ausleihen

Der Command `app:overdue-loans-reminder` gleicht die Tabelle `reminders` mit den überfälligen Ausleihen ab. Eine echte E-Mail wird nicht verschickt.

## Ablauf über Symfony Messenger

```
OverdueLoansReminderCommand ──dispatch──▶ SyncOverdueReminders (Message)
                                              │
                                              ▼
                              SyncOverdueRemindersHandler ──▶ ReminderService::syncOverdueReminders()
```

| Klasse | Aufgabe |
|---|---|
| `App\Command\OverdueLoansReminderCommand` | schickt die Message über den Message-Bus ab und gibt das Ergebnis aus |
| `App\Message\SyncOverdueReminders` | leere Message, „Erinnerungen abgleichen“ |
| `App\MessageHandler\SyncOverdueRemindersHandler` | `#[AsMessageHandler]`, ruft den `ReminderService` auf |
| `App\Service\ReminderService` | die eigentliche Logik (siehe Regeln) |

Die Message ist in `config/packages/messenger.yaml` keinem Transport zugeordnet und wird deshalb **synchron** im selben Prozess verarbeitet. Der Cronjob funktioniert dadurch ohne Worker.

Soll der Abgleich später asynchron laufen, die Message unter `routing` einem Transport zuordnen und einen Worker starten (`bin/console messenger:consume <transport>`). Der Command meldet dann nur noch „Abgleich der Erinnerungen angestoßen.“

## Regeln

- **überfällig** = nicht zurückgegeben (`returnedAt` ist `null`) und `dueAt` liegt in der Vergangenheit
- für jede überfällige Ausleihe gibt es danach genau eine Erinnerung mit dem heutigen Datum (`date`, `overdue_loan_id`)
- entfernt werden Erinnerungen, deren Ausleihe nicht mehr überfällig ist (z. B. zurückgegeben), und Erinnerungen von früheren Tagen
- mehrfaches Ausführen am selben Tag ändert nichts

## Manuell ausführen

```bash
docker compose exec php bin/console app:overdue-loans-reminder   # vom Host
bin/console app:overdue-loans-reminder                           # im Container (make sh)
```

## Täglicher Cronjob

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
