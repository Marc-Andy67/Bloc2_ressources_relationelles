.DEFAULT_GOAL := help
.PHONY: help

# ─── Variables ───────────────────────────────────────────────
DOCKER_COMPOSE = docker compose
PHP = $(DOCKER_COMPOSE) exec app php
COMPOSER = $(DOCKER_COMPOSE) exec app composer
CONSOLE = $(PHP) bin/console

# ─── Help ────────────────────────────────────────────────────
help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

# ─── Setup ───────────────────────────────────────────────────
install: ## Installation complète (build + start + deps)
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d
	$(COMPOSER) install
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

start: ## Démarrer les containers
	$(DOCKER_COMPOSE) up -d

stop: ## Arrêter les containers
	$(DOCKER_COMPOSE) down

restart: stop start ## Redémarrer les containers

# ─── Dev tools ───────────────────────────────────────────────
logs: ## Voir les logs (app)
	$(DOCKER_COMPOSE) logs -f app

sh: ## Shell dans le container app
	$(DOCKER_COMPOSE) exec app sh

db-shell: ## Shell PostgreSQL
	$(DOCKER_COMPOSE) exec db psql -U app app_dev

# ─── Symfony ─────────────────────────────────────────────────
cc: ## Cache clear
	$(CONSOLE) cache:clear

migrate: ## Lancer les migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

migration: ## Créer une nouvelle migration
	$(CONSOLE) doctrine:migrations:diff

fixtures: ## Charger les fixtures
	$(CONSOLE) doctrine:fixtures:load --no-interaction

# ─── Tests ───────────────────────────────────────────────────
test: ## Lancer tous les tests
	$(PHP) bin/phpunit

test-cov: ## Tests avec couverture de code
	$(PHP) bin/phpunit --coverage-html=var/coverage

# ─── Qualité code ────────────────────────────────────────────
lint: ## Lancer le lint (CS-Fixer dry-run)
	$(DOCKER_COMPOSE) exec app php-cs-fixer fix --dry-run --diff

fix: ## Corriger automatiquement le style
	$(DOCKER_COMPOSE) exec app php-cs-fixer fix

stan: ## PHPStan analyse statique
	$(DOCKER_COMPOSE) exec app phpstan analyse

audit: ## Audit de sécurité Composer
	$(COMPOSER) audit

# ─── Docker ──────────────────────────────────────────────────
build: ## Build l'image Docker de production
	docker build --target production -t app:local .

build-dev: ## Build l'image Docker de développement
	docker build --target development -t app:dev .

push: ## Push l'image sur GHCR
	docker push ghcr.io/$(GITHUB_REPOSITORY):latest

# ─── Release ─────────────────────────────────────────────────
release-patch: ## Créer un tag patch (v1.0.x)
	@$(eval VERSION := $(shell git describe --tags --abbrev=0 | awk -F. '{print $$1"."$$2"."$$3+1}'))
	@echo "Creating tag $(VERSION)"
	git tag -a $(VERSION) -m "Release $(VERSION)"
	git push origin $(VERSION)

release-minor: ## Créer un tag minor (v1.x.0)
	@$(eval VERSION := $(shell git describe --tags --abbrev=0 | awk -F. '{print $$1"."$$2+1".0"}'))
	@echo "Creating tag $(VERSION)"
	git tag -a $(VERSION) -m "Release $(VERSION)"
	git push origin $(VERSION)