SHELL := /bin/bash
COMPOSE := docker compose

.PHONY: up down build logs migrate seed shell-backend shell-frontend reset ps

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
