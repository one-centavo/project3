# ---------- Stage 1: Vendor (Composer) ----------
FROM composer:2.10 AS vendor
WORKDIR /app

# Solo los archivos de dependencias primero -> cachea la descarga
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --no-scripts \
    --no-autoloader

# Ahora sí copiamos el código fuente (esto NO invalida la capa de arriba)
COPY database/ database/
COPY app/ app/
COPY routes/ routes/

# Autoload optimizado se genera aparte, una vez que el código está presente
# --no-scripts evita que corra "artisan package:discover" (necesitaría el proyecto completo: artisan, config/, bootstrap/, .env)
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ---------- Stage 2: Frontend (Vite + Tailwind 4) ----------
FROM node:22-alpine AS frontend
WORKDIR /app

RUN apk add --no-cache make g++ python3

COPY package.json pnpm-lock.yaml* vite.config.js ./

# corepack respeta la versión fijada en "packageManager" de package.json,
# igual que en Dockerfile.dev, evitando mismatches de lockfileVersion
RUN corepack enable

# pnpm v10 bloquea por defecto los postinstall scripts de dependencias (ej. esbuild).
# Lo aprobamos explícitamente para builds no interactivos (CI/Docker).
RUN echo "only-built-dependencies[]=esbuild" >> .npmrc

RUN pnpm config set package-import-method copy \
    && pnpm install --frozen-lockfile

COPY resources/ resources/
COPY public/ public/
COPY app/ app/

RUN pnpm run build

# ---------- Stage 3: Runtime (PHP-FPM) ----------
FROM php:8.3-fpm-alpine

ARG user=laravel_user
ARG uid=1000

# Dependencias del sistema + extensiones PHP (paridad con Dockerfile.dev)
RUN apk add --no-cache \
        unzip \
        curl \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        bcmath \
        opcache \
        gd \
        mbstring \
        exif \
        pcntl

RUN adduser -G www-data -u ${uid} -h /home/${user} -D ${user}

WORKDIR /var/www/html

# --chown evita un chown -R costoso después
COPY --chown=${user}:www-data . .
COPY --from=vendor --chown=${user}:www-data /app/vendor/ ./vendor/
COPY --from=frontend --chown=${user}:www-data /app/public/build/ ./public/build/

# Aquí sí corre el discovery: ya tenemos artisan, config/, bootstrap/ y vendor completos
RUN php artisan package:discover --ansi

RUN mkdir -p storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R ${user}:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

USER ${user}

CMD ["php-fpm"]