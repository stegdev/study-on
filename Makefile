COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

fixtload:
	@${CONSOLE} doctrine:fixtures:load

require:
	@${COMPOSER} require $2

encore_dev:
	@${COMPOSE} run node yarn encore dev

encore_prod:
	@${COMPOSE} run node yarn encore production

encore_restart_watch:
	@${COMPOSE} run node yarn encore dev --watch
phpunit:
	@${CONSOLE} doctrine:database:create --env=test --if-not-exists
	@${CONSOLE} doctrine:migrations:migrate --env=test -n
	@${PHP} bin/phpunit

rebuild:
	$(COMPOSE) up -d --no-deps --build $2
