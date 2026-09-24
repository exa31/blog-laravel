# =============================
# Stage 1: Build Frontend Assets
# =============================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
RUN npm run build

# =============================
# Stage 2: Vendor Dependencies
# =============================
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs

# =============================
# Stage 3: Production Runtime
# =============================
FROM dunglas/frankenphp:php8.4-alpine

# Install only required PHP extensions via built-in helper (strips compilation headers automatically)
RUN install-php-extensions \
    pdo_pgsql \
    pdo_mysql \
    gd \
    intl \
    zip \
    bcmath \
    exif \
    pcntl \
    opcache

# Use official production PHP configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app

# Copy application source code
COPY . .

# Copy production vendor from composer-builder and compiled assets from frontend
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Setup storage and cache permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Copy FrankenPHP Caddyfile & Startup Script
COPY frankenphp.conf /app/Caddyfile
COPY start.sh /start.sh
RUN chmod +x /start.sh

ENTRYPOINT ["/start.sh"]
