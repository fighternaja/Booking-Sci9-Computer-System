#!/bin/bash

# Script สำหรับรัน migrations บน Railway
# ใช้ใน Railway Shell หรือ Terminal

echo "🚀 Starting migrations..."

# Run migrations
php artisan migrate --force

echo "✅ Migrations completed!"

# Create storage link
php artisan storage:link

echo "✅ Storage link created!"

echo "🎉 Setup complete!"

