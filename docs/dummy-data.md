# Dummy-Daten

Der Command `app:create-dummy-data` (`App\Command\CreateDummyDataCommand`) legt zusammenhängende Testdaten an: Bücher, Mitglieder, Ausleihen und die Erinnerungen für die überfälligen Ausleihen.

```bash
bin/console app:create-dummy-data                                      # 50 Bücher, 50 Mitglieder, 30 Ausleihen
bin/console app:create-dummy-data --books=200 --members=100 --loans=80
```

## Regeln

- die Ausleihen liegen in den letzten 30 Tagen, ein Teil ist also überfällig (Leihfrist 2 Wochen)
- rund 30 % sind bereits zurückgegeben
- offene Ausleihen reduzieren `availableCopies`, Bücher ohne verfügbare Kopien werden dafür nicht verwendet
- für die überfälligen Ausleihen werden Erinnerungen angelegt, siehe [Erinnerungen](reminders.md)

Die Daten kommen zu vorhandenen dazu. Beim Docker-Start läuft der Command automatisch, aber nur bei leerer Datenbank, siehe [Setup](setup.md).
