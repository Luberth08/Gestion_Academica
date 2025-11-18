#!/bin/bash

# Establecer permisos
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Instalar dependencias
composer install --no-dev --optimize-autoloader --no-scripts

# Ejecutar scripts post-install
composer run-script --no-dev post-autoload-dump

# 🔥 LIMPIAR CACHÉ COMPLETAMENTE ANTES DE CACHEAR
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 🔥 VERIFICAR QUE LAS VARIABLES ESTÉN DISPONIBLES
echo "=== VERIFICACIÓN DE VARIABLES ==="
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST" 
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"

# 🔥 SOLO DESPUÉS DE LIMPIAR, GENERAR CACHE
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar permisos finales
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# 🔥 VERIFICAR CONFIGURACIÓN CACHEADA
echo "=== CONFIGURACIÓN CACHEADA ==="
php artisan tinker --execute="echo 'DB Default: ' . config('database.default') . PHP_EOL; echo 'DB Host: ' . config('database.connections.pgsql.host') . PHP_EOL;"