#!/bin/sh

# Buat symlink storage jika belum ada
if [ ! -L "/app/public/storage" ]; then
  php artisan storage:link
fi

# Pastikan permission benar
chown -R www-data:www-data /app/storage /app/public/storage /app/bootstrap/cache

# Start Laravel via FrankenPHP
php artisan octane:frankenphp
