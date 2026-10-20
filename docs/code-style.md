# Code-Style

Geprüft und korrigiert mit `php-cs-fixer`, die Regeln stehen in `.php-cs-fixer.dist.php`.

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff   # nur prüfen
vendor/bin/php-cs-fixer fix                    # automatisch korrigieren
```

## Projektvorgaben

- `declare(strict_types=1);` in jeder Datei
- Typen an allen Parametern, Properties und Rückgabewerten
- PSR-konformer Code-Style
- PHPDoc an jeder Klasse und jeder öffentlichen Methode, jeweils mit:
  - Kurzbeschreibung
  - `@param` für jeden Parameter
  - `@return`
