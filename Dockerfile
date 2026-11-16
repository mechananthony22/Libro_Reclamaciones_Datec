# ========================================================
# Etapa 1: Compilación de Assets del Frontend (Node 22)
# ========================================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app

# Instalar dependencias de Node
COPY package.json package-lock.json* ./
RUN npm ci || npm install

# Copiar archivos fuente del frontend y configuraciones
COPY resources resources/
COPY public public/
COPY vite.config.ts tsconfig*.json ./

# Compilar assets estáticos con Vite
RUN npm run build

# ========================================================
# Etapa 2: Runtime de Producción (PHP 8.4-FPM + Nginx)
# ========================================================
FROM php:8.4-fpm-alpine

# Instalar paquetes del sistema necesarios
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    zip \
    unzip \
    git

# Configurar y compilar extensiones oficiales de PHP requeridas
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        bcmath \
        intl \
        opcache \
        pcntl \
        exif

# Copiar binario de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Preparar directorios de runtime de Nginx y Supervisor
RUN mkdir -p /run/nginx /var/log/supervisor /var/log/nginx

# Copiar configuraciones personalizadas del servidor
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copiar definición de Composer e instalar dependencias de producción
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar el código fuente completo del proyecto
COPY . .

# Copiar los assets compilados de la etapa frontend
COPY --from=frontend-builder /app/public/build ./public/build

# Generar el autoloader optimizado para producción de Laravel
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Ajustar permisos para el usuario www-data
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

