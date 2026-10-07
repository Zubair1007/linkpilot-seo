# ==========================================
# Stage 1: Build Frontend Assets (Vite + React)
# ==========================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY vite.config.js tsconfig.json ./
COPY resources/ ./resources/
COPY public/ ./public/

RUN npm run build

# ==========================================
# Stage 2: Production PHP + Nginx Environment
# ==========================================
FROM php:8.2-fpm-alpine AS production

LABEL maintainer="LinkPilot SEO Team <ops@linkpilot.io>"
LABEL description="Production container for LinkPilot SEO on Render / Supabase free tier"

# Install system dependencies & PostgreSQL client libraries
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    sqlite-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        pdo_sqlite \
        pdo_mysql \
        bcmath \
        mbstring \
        zip \
        opcache \
        gd \
        xml

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source code
COPY . .

# Copy compiled Vite assets from Stage 1
COPY --from=frontend-builder /app/public/build ./public/build

# Install PHP dependencies without dev packages
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy Docker service configurations
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ensure storage directories exist with correct permissions
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    /var/log/supervisor \
    /var/log/nginx \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render default port
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
