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

# Jalankan database migrations secara async di background
echo "Menjalankan database migration di background..."
(php artisan migrate --force --graceful || true) &

# Jalankan Laravel Octane dengan FrankenPHP
echo "Menjalankan Laravel dengan octane FrankenPHP..."
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000


