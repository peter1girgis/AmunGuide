#!/bin/bash
set -e

echo "🚀 Starting Laravel Application..."

# Clear optimization caches
echo "🧹 Clearing optimization caches..."
php artisan optimize:clear

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Seed database (اختياري - اضف هذا السطر إذا كان لديك seeders)
# php artisan db:seed --force

echo "🔗 Creating storage symlink..."
php artisan storage:link

# Start the server
echo "⚡ Starting PHP server..."
php artisan serve --host=0.0.0.0 --port=$PORT
