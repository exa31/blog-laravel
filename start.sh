#!/bin/sh

set -e

# Buat symlink storage jika belum ada
if [ ! -L "/app/public/storage" ]; then
  echo "Membuat symlink storage..."
  php artisan storage:link
fi

# Pastikan permission benar
echo "Mengatur permission..."
chown -R www-data:www-data /app/storage /app/public/storage /app/bootstrap/cache

# Jalankan database migrations otomatis
echo "Menjalankan database migration..."
php artisan migrate --force --graceful

# Jalankan Laravel Octane dengan FrankenPHP
echo "Menjalankan Laravel dengan octane FrankenPHP..."
#php artisan octane:frankenphp
php artisan octane:start --server=frankenphp --log-level=debug


