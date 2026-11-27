SHELL := /bin/bash
COMPOSE := docker compose

.PHONY: up down build logs migrate seed seed-demo test lint lint-all typecheck shell-backend shell-frontend reset ps

## Levanta el stack completo (construye si hace falta)
up:
	$(COMPOSE) up -d --build

## Baja los contenedores sin borrar datos
down:
	$(COMPOSE) down

## Construye las imágenes
build:
	$(COMPOSE) build

## Sigue los logs de todos los servicios
logs:
	$(COMPOSE) logs -f --tail=100

## Ejecuta las migraciones pendientes
migrate:
	$(COMPOSE) exec backend php artisan migrate --force

## Ejecuta los seeders (crea los roles base)
seed:
	$(COMPOSE) exec backend php artisan db:seed --force

## Crea los usuarios demo de los tres roles (solo desarrollo)
seed-demo:
	$(COMPOSE) exec backend php artisan db:seed --class=SeguridadDemoUsersSeeder --force

## Ejecutar toda la suite backend
test:
	$(COMPOSE) exec -T backend php artisan test

## Verificar formato de los PHP modificados/nuevos (Pint)
lint:
	@files="$$(git ls-files -m -o --exclude-standard '*.php' | sed 's#^backend/##')"; \
	if [ -z "$$files" ]; then \
		echo "Sin archivos PHP modificados."; \
	else \
		$(COMPOSE) exec -T backend ./vendor/bin/pint --test $$files; \
	fi

## Verificar formato de todo el backend (puede incluir deuda previa)
lint-all:
	$(COMPOSE) exec -T backend ./vendor/bin/pint --test

## Verificar tipos del frontend Angular
typecheck:
	$(COMPOSE) exec -T frontend npx tsc --noEmit -p tsconfig.app.json

## Shell dentro del backend (como el UID/GID del host)
shell-backend:
	$(COMPOSE) exec backend bash

## Shell dentro del frontend
shell-frontend:
	$(COMPOSE) exec frontend sh

## Baja TODO y elimina los volúmenes (BD, vendor y node_modules)
reset:
	$(COMPOSE) down -v --remove-orphans

## Estado de los contenedores
ps:
	$(COMPOSE) ps
