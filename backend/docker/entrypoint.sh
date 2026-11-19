#!/bin/sh
set -eu

cd /var/www/html

# ---------------------------------------------------------------------------
# Cachés de bootstrap
# El bind mount puede traer packages.php/services.php generados en el host
# con paquetes de desarrollo que no existen en la imagen de producción.
# ---------------------------------------------------------------------------
mkdir -p bootstrap/cache
rm -f bootstrap/cache/*.php

# ---------------------------------------------------------------------------
# .env y APP_KEY
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=base64:' .env; then
    echo "[talitacumi] Generando APP_KEY..."
    php artisan key:generate --force
fi

# ---------------------------------------------------------------------------
# Permisos de escritura (bind mount del host)
# ---------------------------------------------------------------------------
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Si el volumen de vendor está vacío (primer arranque o cambio de volumen),
# se instalan las dependencias desde el lockfile.
if [ ! -f vendor/autoload.php ]; then
    echo "[talitacumi] vendor/ vacío; ejecutando composer install..."
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
fi

# ---------------------------------------------------------------------------
# Espera a PostgreSQL
# ---------------------------------------------------------------------------
if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    echo "[talitacumi] Esperando a PostgreSQL en ${DB_HOST:-db}:${DB_PORT:-5432}..."
    until php -r '
        try {
            $host = getenv("DB_HOST") ?: "db";
            $port = getenv("DB_PORT") ?: "5432";
            $database = getenv("DB_DATABASE") ?: "talitacumi";
            $username = getenv("DB_USERNAME") ?: "talitacumi";
            $password = getenv("DB_PASSWORD") ?: "";
            new PDO("pgsql:host={$host};port={$port};dbname={$database}", $username, $password);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getMessage().PHP_EOL);
            exit(1);
        }
    ' 2>/dev/null; do
        sleep 2
    done
    echo "[talitacumi] PostgreSQL disponible."
fi

# ---------------------------------------------------------------------------
# Migraciones y storage link (idempotentes)
# ---------------------------------------------------------------------------
attempt=0
until php artisan migrate --force; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 5 ]; then
        echo "[talitacumi] No se pudieron ejecutar las migraciones." >&2
        exit 1
    fi
    echo "[talitacumi] Reintentando migraciones ($attempt/5)..." >&2
    sleep 2
done

if [ ! -L public/storage ]; then
    php artisan storage:link --force || true
fi

# Enlace relativo: válido tanto dentro del contenedor como en el bind mount del host.
if [ -L public/storage ] && [ "$(readlink public/storage)" != "../storage/app/public" ]; then
    ln -sfn ../storage/app/public public/storage
fi

echo "[talitacumi] Iniciando: $*"
exec "$@"
