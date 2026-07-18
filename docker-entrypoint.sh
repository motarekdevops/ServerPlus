#!/bin/bash
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate
fi

touch database/database.sqlite

php artisan migrate --force

php artisan make:filament-user \
    --name="Admin" \
    --email="admin@serverplus.local" \
    --password="password" \
    --no-interaction || true

php artisan queue:work --daemon &
php artisan schedule:work &

php artisan serve --host=0.0.0.0 --port=8000
