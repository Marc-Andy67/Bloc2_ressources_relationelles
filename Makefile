.DEFAULT_GOAL := help
.PHONY: help

# ─── Variables ───────────────────────────────────────────────
DOCKER_COMPOSE = docker compose
PHP            = $(DOCKER_COMPOSE) exec app php
COMPOSER       = $(DOCKER_COMPOSE) exec app composer
CONSOLE        = $(PHP) bin/console

# ─── Help ────────────────────────────────────────────────────
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

# ─── Setup ───────────────────────────────────────────────────
install: ## Installation complete (pull image + start + migrations + fixtures)
	$(DOCKER_COMPOSE) pull
	$(DOCKER_COMPOSE) up -d
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(CONSOLE) doctrine:fixtures:load --no-interaction

reset: ## Reset complet (supprime volumes + relance install)
	$(DOCKER_COMPOSE) down -v
	$(MAKE) install

start: ## Demarrer les containers
	$(DOCKER_COMPOSE) up -d

stop: ## Arreter les containers
	$(DOCKER_COMPOSE) down

restart: stop start ## Redemarrer les containers

# ─── Dev tools ───────────────────────────────────────────────
logs: ## Voir les logs (app)
	$(DOCKER_COMPOSE) logs -f app

logs-all: ## Voir tous les logs
	$(DOCKER_COMPOSE) logs -f

sh: ## Shell dans le container app
	$(DOCKER_COMPOSE) exec app sh

db-shell: ## Shell MySQL
	$(DOCKER_COMPOSE) exec db mysql -u app -psecret app_dev

adminer: ## Lancer Adminer (interface DB)
	$(DOCKER_COMPOSE) --profile tools up -d adminer
	@echo "Adminer disponible sur http://localhost:8081"

mail: ## Lancer Mailpit (interface mail)
	$(DOCKER_COMPOSE) --profile tools up -d mail
	@echo "Mailpit disponible sur http://localhost:8025"

tools: ## Lancer tous les outils (adminer + mailpit)
	$(DOCKER_COMPOSE) --profile tools up -d
	@echo "Adminer -> http://localhost:8081"
	@echo "Mailpit -> http://localhost:8025"

# ─── Symfony ─────────────────────────────────────────────────
cc: ## Cache clear
	$(CONSOLE) cache:clear

migrate: ## Lancer les migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

migration: ## Creer une nouvelle migration
	$(CONSOLE) doctrine:migrations:diff

fixtures: ## Charger les fixtures
	$(CONSOLE) doctrine:fixtures:load --no-interaction

assets: ## Recompiler les assets (tailwind + importmap)
	$(CONSOLE) tailwind:build --env=prod
	$(CONSOLE) importmap:install
	$(CONSOLE) asset-map:compile

# ─── Tests ───────────────────────────────────────────────────
test: ## Lancer tous les tests
	$(PHP) bin/phpunit

test-cov: ## Tests avec couverture de code
	$(PHP) bin/phpunit --coverage-html=var/coverage

# ─── Qualite code ────────────────────────────────────────────
lint: ## Lancer le lint (CS-Fixer dry-run)
	$(DOCKER_COMPOSE) exec app php-cs-fixer fix --dry-run --diff

fix: ## Corriger automatiquement le style
	$(DOCKER_COMPOSE) exec app php-cs-fixer fix

stan: ## PHPStan analyse statique
	$(DOCKER_COMPOSE) exec app phpstan analyse

audit: ## Audit de securite Composer
	$(COMPOSER) audit

# ─── Docker ──────────────────────────────────────────────────
build: ## Build l'image Docker de production
	docker build --target production -t app:local .

build-dev: ## Build l'image Docker de developpement
	docker build --target development -t app:dev .

push: ## Push l'image sur GHCR
	docker push ghcr.io/$(GITHUB_REPOSITORY):latest

# ─── Release ─────────────────────────────────────────────────
release-patch: ## Creer un tag patch (v1.0.x)
	@$(eval VERSION := $(shell git describe --tags --abbrev=0 | awk -F. '{print $$1"."$$2"."$$3+1}'))
	@echo "Creating tag $(VERSION)"
	git tag -a $(VERSION) -m "Release $(VERSION)"
	git push origin $(VERSION)

release-minor: ## Creer un tag minor (v1.x.0)
	@$(eval VERSION := $(shell git describe --tags --abbrev=0 | awk -F. '{print $$1"."$$2+1".0"}'))
	@echo "Creating tag $(VERSION)"
	git tag -a $(VERSION) -m "Release $(VERSION)"
	git push origin $(VERSION)