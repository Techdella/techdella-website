# TechDella local + deploy commands (§4.2, §5.3). Run `make help`.
SHELL := /bin/bash
SITE  ?= marketing
-include .env
export

COMPOSE := docker compose
SSH      = ssh -p $(HOSTINGER_SSH_PORT) $(HOSTINGER_SSH_USER)@$(HOSTINGER_SSH_HOST)
SITE_PATH = $(PATH_$(shell echo $(SITE) | tr a-z A-Z))
PORT_marketing := 8081
PORT_journal   := 8082
PORT_studios   := 8083

.PHONY: help up down build watch seed setup pull-db pull-uploads share test lint e2e lighthouse wp deploy-staging deploy-prod rollback logs

help: ## List commands
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[1m%-16s\033[0m %s\n",$$1,$$2}'

node_modules: package.json
	npm install
	@touch node_modules

build: node_modules ## Build theme CSS/JS/images
	npm run build

up: build ## Start the stack; install WP + plugins + theme on first run
	$(COMPOSE) up -d
	./docker/bin/setup.sh

setup: ## Re-run the WP install/config step for SITE (or all)
	./docker/bin/setup.sh $(if $(filter command line,$(origin SITE)),$(SITE),)

down: ## Stop the stack (data volumes kept)
	$(COMPOSE) down

logs: ## Tail container logs
	$(COMPOSE) logs -f --tail=100

watch: node_modules ## Rebuild CSS/JS on change
	npm run watch

wp: ## Run WP-CLI on SITE: make wp SITE=journal ARGS="plugin list"
	$(COMPOSE) exec -u www-data wp-$(SITE) wp $(ARGS)

seed: ## Import content/ as DRAFTS + menus + options (§10)
	@for s in marketing journal studios; do \
	  $(COMPOSE) exec -T -u www-data wp-$$s wp td import-content /var/www/repo/content --status=draft; \
	  $(COMPOSE) exec -T -u www-data wp-$$s wp td setup; \
	done

pull-db: ## Copy SITE's production DB to local (read-only on prod)
	@test -n "$(HOSTINGER_SSH_HOST)" || (echo "Set HOSTINGER_* and PATH_* in .env" && exit 1)
	$(SSH) "cd $(SITE_PATH) && wp db export - --single-transaction" | gzip > /tmp/td-$(SITE).sql.gz
	gunzip -c /tmp/td-$(SITE).sql.gz | $(COMPOSE) exec -T -u www-data wp-$(SITE) wp db import -
	$(COMPOSE) exec -T -u www-data wp-$(SITE) wp search-replace "$$($(SSH) "cd $(SITE_PATH) && wp option get home")" "http://localhost:$(PORT_$(SITE))" --all-tables --skip-columns=guid
	$(COMPOSE) exec -T -u www-data wp-$(SITE) wp theme activate techdella
	rm -f /tmp/td-$(SITE).sql.gz

pull-uploads: ## rsync SITE's uploads from production (read-only)
	@test -n "$(HOSTINGER_SSH_HOST)" || (echo "Set HOSTINGER_* and PATH_* in .env" && exit 1)
	mkdir -p .uploads/$(SITE)
	rsync -az --delete -e "ssh -p $(HOSTINGER_SSH_PORT)" $(HOSTINGER_SSH_USER)@$(HOSTINGER_SSH_HOST):$(SITE_PATH)/wp-content/uploads/ .uploads/$(SITE)/
	$(COMPOSE) cp .uploads/$(SITE)/. wp-$(SITE):/var/www/html/wp-content/uploads/
	$(COMPOSE) exec -T wp-$(SITE) chown -R www-data:www-data /var/www/html/wp-content/uploads

share: ## Temporary public tunnel for SITE (Cloudflare quick tunnel)
	cloudflared tunnel --url http://localhost:$(PORT_$(SITE))

lint: ## PHPCS + PHPStan + JS/CSS checks
	composer lint
	npm run lint

e2e: ## Playwright e2e + axe against localhost
	npm run test:e2e

lighthouse: ## Lighthouse CI against localhost
	npm run test:lighthouse

test: lint e2e lighthouse ## Everything in §16 that is automated

deploy-staging: ## Deploy theme + mu-plugins to staging (normally done by CI)
	./scripts/deploy.sh staging

deploy-prod: ## Deploy to production (normally done by CI on a v* tag)
	./scripts/deploy.sh production

rollback: ## Redeploy a previous tag: make rollback TAG=v1.2.3
	@test -n "$(TAG)" || (echo "TAG=vX.Y.Z required" && exit 1)
	git fetch --tags && git checkout $(TAG) && npm ci && npm run build && ./scripts/deploy.sh production; git checkout -
