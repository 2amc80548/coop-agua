#!/bin/bash
set -e

echo "⏳ Esperando a que PostgreSQL ($DB_HOST:$DB_PORT) esté disponible..."
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME"; do
  sleep 2
done

echo "✅ PostgreSQL está listo."

# Si la carpeta vendor no existe, ejecutar composer install
if [ ! -d "/var/www/vendor" ]; then
    echo "📦 Instalando dependencias de PHP con Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generar APP_KEY si no está configurada
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --no-interaction
fi

# Asegurar permisos en carpetas de almacenamiento
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

echo "🚀 Iniciando PHP-FPM..."
exec php-fpm
