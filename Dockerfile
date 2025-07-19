# Tahap 1: Build frontend
FROM node:20 AS frontend

WORKDIR /app

COPY package*.json vite.config.js ./
COPY resources ./resources

RUN npm install && npm run build


# Tahap 2: Laravel + FrankenPHP
FROM dunglas/frankenphp

# Install dependencies dan PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    unzip git curl vim \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpq-dev \
    locales \
    jpegoptim optipng pngquant gifsicle \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        gd \
        intl \
        zip

WORKDIR /app

# Copy seluruh project Laravel
COPY . .

# Copy hasil frontend build
COPY --from=frontend /app/resources ./resources
COPY --from=frontend /app/public/build ./public/build

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    composer install --no-dev --optimize-autoloader

# Set permission Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# 🟢 Simpan Caddyfile di lokasi aman
COPY frankenphp.conf /app/Caddyfile

# Start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

ENTRYPOINT ["/start.sh"]
