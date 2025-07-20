# =============================
# Tahap 1: Build frontend
# =============================
FROM node:20 AS frontend

WORKDIR /app

# ⬇️ Tambahkan dua file ini biar Tailwind bisa di-build
COPY package*.json vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources


RUN npm install && npm run build

# =============================
# Tahap 2: Laravel + FrankenPHP
# =============================
FROM dunglas/frankenphp

# Install dependencies dan PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential unzip git curl vim \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    libicu-dev libonig-dev libpq-dev locales \
    jpegoptim optipng pngquant gifsicle \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql pdo_pgsql mbstring bcmath exif pcntl gd intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy Laravel backend tanpa folder yang nggak perlu
COPY . .

# ⚠️ Hapus folder `public/build` default sebelum replace dari frontend
RUN rm -rf public/build

# ✅ Copy hasil Vite build dari tahap frontend
COPY --from=frontend /app/public/build ./public/build

# ❗ resources tidak perlu di-copy ulang (sudah ada di Laravel), kecuali kamu build-only dari sana

# ✅ Install Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    composer install --no-dev --optimize-autoloader

# ✅ Set permission Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# ✅ Copy konfigurasi Caddy untuk FrankenPHP
COPY frankenphp.conf /app/Caddyfile

# ✅ Start script (harus berisi perintah `php artisan migrate`, dll kalau perlu)
COPY start.sh /start.sh
RUN chmod +x /start.sh

# ✅ Jalankan Laravel via start.sh
ENTRYPOINT ["/start.sh"]
