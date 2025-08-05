# Dockerfile para Laravel Backend
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    postgresql-client \
    zip \
    unzip \
    nginx \
    supervisor \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de composer primero para aprovechar cache de Docker
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader && composer clear-cache

# Copiar código fuente
COPY . .

# Ejecutar composer dump-autoload
RUN composer dump-autoload --optimize

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copiar configuraciones de Nginx, SSL y Supervisor
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/nginx/nginx.conf.dev /etc/nginx/nginx.conf.dev
COPY docker/nginx/nginx.conf.prod /etc/nginx/nginx.conf.prod
COPY docker/nginx/ssl/ /etc/nginx/ssl/
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Crear directorio para logs y dar permisos al script
RUN mkdir -p /var/log/supervisor && \
    chmod +x /usr/local/bin/entrypoint.sh && \
    chmod 644 /etc/nginx/ssl/cert.pem && \
    chmod 600 /etc/nginx/ssl/key.pem && \
    chmod 644 /etc/nginx/ssl/dhparam.pem

# Exponer puertos
EXPOSE 80 443

# Comando de inicio con el script de entrada
CMD ["/usr/local/bin/entrypoint.sh"]
