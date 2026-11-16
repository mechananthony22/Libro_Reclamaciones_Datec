#!/bin/sh
set -e

echo "Iniciando contenedor de DatecLibro..."

# Garantizar permisos de carpetas de almacenamiento y caché
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/public \
         /var/www/html/storage/app/custodia \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Enlace simbólico de storage público
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creando enlace simbolico de storage..."
    php artisan storage:link || true
fi

# Esperar conexión a PostgreSQL si DB_HOST está configurado
if [ -n "$DB_HOST" ]; then
    echo "Esperando conexión con PostgreSQL en $DB_HOST:${DB_PORT:-5432}..."
    retries=30
    until php -r "try { new PDO('pgsql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:'5432').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        retries=$((retries - 1))
        if [ $retries -le 0 ]; then
            echo "Error: No se pudo conectar a PostgreSQL después de múltiples intentos."
            break
        fi
        sleep 2
    done
    echo "Conexión a PostgreSQL confirmada."
fi

# Ejecutar migraciones automáticas si está habilitado (por defecto en el contenedor principal)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Ejecutando migraciones pendientes..."
    php artisan migrate --force
fi

# Optimización en entorno de producción
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizando caches de Laravel para producción..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
fi

echo "DatecLibro listo. Iniciando proceso: $@"
exec "$@"

