# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.3.36

# ---------------------------------------------------------------------------
# Dependencias PHP: se instalan en una etapa separada para no ensuciar la
# imagen final. La plataforma está fijada en composer.json a PHP 8.3.36.
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-cli-bookworm AS vendor

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.11.2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ---------------------------------------------------------------------------
# Imagen final: PHP 8.3 FPM con las extensiones requeridas por Laravel.
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-bookworm AS runtime

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Usuario con el UID/GID del host para que storage/ y bootstrap/cache
# conserven permisos correctos con el bind mount.
RUN groupadd -g "${GID}" app \
    && useradd -u "${UID}" -g app -m -s /bin/bash app

COPY --from=composer:2.11.2 /usr/bin/composer /usr/local/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-talitacumi.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-talitacumi-opcache.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-talitacumi.conf

COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R app:app /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER app

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm", "-F"]
