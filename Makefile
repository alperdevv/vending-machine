# Thin wrapper over Docker Compose so the toolchain runs identically on every
# machine and in CI. Each target shells into the `app` service defined in
# docker-compose.yml.
DC  := docker compose
RUN := $(DC) run --rm app

.DEFAULT_GOAL := help

.PHONY: help build install test stan cs cs-fix run

help: ## List the available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

build: ## Build the Docker image
	$(DC) build

install: ## Install Composer dependencies
	$(RUN) composer install

run: ## Start the vending machine
	$(RUN) php bin/vending

test: ## Run the test suite
	$(RUN) vendor/bin/phpunit

stan: ## Run static analysis
	$(RUN) vendor/bin/phpstan analyse

cs: ## Check the coding style
	$(RUN) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix the coding style
	$(RUN) vendor/bin/php-cs-fixer fix
