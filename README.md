# Sistema TalitaCumi

Sistema de Gestión de Turnos, Inscripciones y Cobros del **Centro Integral de Desarrollo Infantil TalitaCumi** (Yacuiba, Bolivia).

Stack: **Laravel 13 + Angular 22 + PostgreSQL 16 + Nginx + Docker**.

---

## Requisitos

- Docker Desktop con Docker Engine y Docker Compose v2.
- Git.
- No es necesario instalar PHP, Composer, Node ni PostgreSQL en el host.

Versiones fijadas:

| Componente | Versión |
|---|---|
| PHP | 8.3.36 FPM |
| Laravel | 13.43 |
| Composer | 2.11.2 |
| Angular | 22.3 |
| Node | 22.23.5 LTS |
| PostgreSQL | 16.16 |
| Nginx | 1.30.7 |

---

## Cómo levantar el sistema

```bash
cd /home/grilloportatil/Programing/TalitaCumi

# Crear el .env si no existe (el repositorio ya incluye uno de desarrollo)
cp .env.example .env

# Levantar todo (construye imágenes la primera vez)
docker compose up -d --build

# Equivalente:
make up
```

El primer arranque puede tardar varios minutos porque compila las extensiones de PHP e instala las dependencias de Angular.

Para seguir el arranque:

```bash
docker compose logs -f
# o
make logs
```

---

## URLs y puertos

| Servicio | URL / puerto |
|---|---|
| Aplicación Angular | http://localhost:8080 |
| API Laravel | http://localhost:8080/api |
| Healthcheck API + BD | http://localhost:8080/api/health |
| PostgreSQL desde el host | `localhost:5433` |
| PostgreSQL dentro de Docker | `db:5432` |

El puerto `5433` se usa en el host porque el `5432` local suele estar ocupado. Ambos puertos son configurables en `.env`:

```dotenv
APP_PORT=8080
POSTGRES_PORT=5433
```

Después de cambiarlos:

```bash
docker compose up -d
```

---

## Credenciales de desarrollo

| Dato | Valor |
|---|---|
| Base de datos | `talitacumi` |
| Usuario | `talitacumi` |
| Contraseña | `talitacumi_dev_password` |
| Host (desde el host) | `localhost` |
| Puerto (desde el host) | `5433` |

> Son credenciales de ejemplo para desarrollo. Cámbialas antes de desplegar en producción.

Conexión manual:

```bash
psql -h localhost -p 5433 -U talitacumi -d talitacumi
```

---

## Migraciones y seeders

Las migraciones se ejecutan automáticamente cada vez que arranca el contenedor `backend` (el `entrypoint` espera a PostgreSQL y ejecuta `php artisan migrate --force`).

Comandos manuales:

```bash
make migrate
make seed
make seed-demo

# Equivalente:
docker compose exec backend php artisan migrate --force
docker compose exec backend php artisan db:seed --force
docker compose exec backend php artisan db:seed --class=SeguridadDemoUsersSeeder --force
```

`make seed-demo` crea los tres usuarios demo con contraseña `Password12` (solo desarrollo; el seeder se bloquea en producción).

Ver el estado de las migraciones:

```bash
docker compose exec backend php artisan migrate:status
```

---

## Comandos principales

```text
make up              # docker compose up -d --build
make down            # baja contenedores (conserva la BD)
make build           # reconstruye imágenes
make logs            # logs en vivo
make migrate         # migraciones
make seed            # crea los roles base (superadmin, administracion, recepcion)
make seed-demo       # crea los usuarios demo de los tres roles (solo desarrollo)
make shell-backend   # shell del backend
make shell-frontend  # shell del frontend
make ps              # estado de contenedores
make reset           # baja todo y BORRA los volúmenes (BD incluida)
```

`make reset` elimina la base de datos, `vendor` y `node_modules`; úsalo solo cuando quieras empezar de cero.

---

## Ver el proyecto en Docker Desktop

1. Abre **Docker Desktop**.
2. Ve a **Containers**.
3. El stack aparece agrupado como **`sistema-talitacumi`**.
4. Dentro del grupo verás:
   - `sistema-talitacumi-db`
   - `sistema-talitacumi-backend`
   - `sistema-talitacumi-frontend`
   - `sistema-talitacumi-nginx`

También puedes comprobarlo por terminal:

```bash
docker compose ls
docker compose ps
```

El nombre del proyecto se define con `name: sistema-talitacumi` en `docker-compose.yml`; la red es `sistema-talitacumi-net` y los volúmenes llevan el prefijo `sistema-talitacumi-`.

---

## Arquitectura

```text
Navegador
   │
   ▼
Nginx :8080
   ├── /api, /sanctum ──► php-fpm (backend:9000) ──► PostgreSQL (db:5432)
   └── / ───────────────► ng serve (frontend:4200)
```

- **Desarrollo (por defecto):** bind mounts del código + volúmenes para `vendor` y `node_modules`. Angular corre con `ng serve --poll 1000` para hot reload estable sobre Docker.
- **Producción:** `frontend/Dockerfile` incluye los stages `build` y `prod`; este último sirve los estáticos con Nginx. El compose de desarrollo usa el stage `dev`.

La pantalla inicial consulta `GET /api/health` y muestra **“Backend conectado”** cuando Laravel responde y PostgreSQL está disponible.

---

## Estructura del proyecto

```text
TalitaCumi/
├── .env / .env.example
├── .gitignore
├── Makefile
├── README.md
├── docker-compose.yml
├── docker/nginx/default.conf
├── backend/                  # Laravel 13
│   ├── Dockerfile
│   ├── .dockerignore
│   ├── docker/entrypoint.sh
│   ├── docker/php/
│   ├── app/Http/Controllers/Api/HealthController.php
│   ├── config/cors.php
│   ├── routes/api.php
│   └── ...
├── frontend/                 # Angular 22
│   ├── Dockerfile
│   ├── .dockerignore
│   ├── docker/nginx.conf
│   ├── src/app/services/health.service.ts
│   └── ...
└── Docs/                     # documentación funcional
```

---

## Roles y autenticación

- **Laravel Sanctum** instalado para autenticación por token.
- **spatie/laravel-permission** instalado para roles y permisos.
- Los traits `HasApiTokens` y `HasRoles` ya están aplicados al modelo `User`.
- Las migraciones de Sanctum y de permisos ya están publicadas.
- `make seed` crea los roles base: `superadmin`, `administracion` y `recepcion`.
- `make seed-demo` crea usuarios demo para iniciar sesión (solo desarrollo):
  - Superadmin: `superadmin.demo` / `Password12`
  - Administración: `admin.demo` / `Password12`
  - Recepción: `recepcion.demo` / `Password12`
- La contraseña demo se puede cambiar con `DEMO_PASSWORD` en `backend/.env`. El seeder no resetea usuarios existentes y se bloquea si `APP_ENV=production`.

---

## Configuración regional

- Idioma: `es`
- Zona horaria: `America/La_Paz`
- Moneda: `BOB` / símbolo `Bs`
- Variables: `APP_LOCALE`, `APP_TIMEZONE`, `APP_CURRENCY`, `APP_CURRENCY_SYMBOL`

---

## Permisos en Linux

El stack corre con el UID/GID definidos en `.env` (`UID=1000`, `GID=1000` por defecto). Compruébalos con:

```bash
id -u
id -g
```

Si tu usuario tiene otros valores, edita `.env` y reconstruye:

```bash
make build
make up
```

Si aparecen errores de escritura en `storage/` o `bootstrap/cache`:

```bash
sudo chown -R "$(id -u):$(id -g)" backend/storage backend/bootstrap/cache
```

---

## Troubleshooting

```bash
# Estado y salud
docker compose ps
docker compose logs --tail=100 backend
docker compose logs --tail=100 frontend
docker compose logs --tail=100 db

# Probar la API
curl http://localhost:8080/api/health

# Ver migraciones
docker compose exec backend php artisan migrate:status
```

Si cambias `composer.json` o `package.json`:

```bash
make build
# Si el volumen conserva dependencias antiguas:
docker compose down
docker volume rm sistema-talitacumi-vendor sistema-talitacumi-node-modules
make up
```

No borres `sistema-talitacumi-db-data` salvo que quieras perder la base de datos.
