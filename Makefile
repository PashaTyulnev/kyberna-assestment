.PHONY: sh

sh: ## Shell im laufenden PHP-Container öffnen
	docker compose exec php bash
