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

# Jalankan Laravel Octane dengan FrankenPHP
echo "Menjalankan Laravel Octane dengan FrankenPHP..."
php artisan octane:frankenphp
