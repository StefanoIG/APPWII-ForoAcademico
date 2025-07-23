#!/bin/bash

# Script de inicialización para el contenedor Laravel
set -e

echo "🚀 Iniciando configuración del contenedor Laravel..."

# Verificar si el archivo .env existe, si no, crearlo desde .env.example
if [ ! -f /var/www/html/.env ]; then
    echo "📄 Creando archivo .env desde .env.example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# PASO 1: Generar APP_KEY PRIMERO (debe ir antes de JWT para evitar corrupción)
if ! grep -q "APP_KEY=base64:" /var/www/html/.env || grep -q "APP_KEY=$" /var/www/html/.env || grep -q "APP_KEY=\"\"" /var/www/html/.env; then
    echo "🔑 Generando APP_KEY..."
    cd /var/www/html && php artisan key:generate --force --no-interaction
    echo "✅ APP_KEY generada exitosamente"
fi

# PASO 2: Configurar JWT DESPUÉS (para que no se corrompa con key:generate)
echo "🔐 Configurando JWT..."
# Generar JWT_SECRET seguro sin caracteres problemáticos
JWT_SECRET=$(openssl rand -hex 32)

# Eliminar configuración JWT existente si existe
sed -i '/# JWT Configuration/,/JWT_ALGO=/d' /var/www/html/.env

# Agregar configuración JWT limpia al final
echo "" >> /var/www/html/.env
echo "# JWT Configuration" >> /var/www/html/.env
echo "JWT_SECRET=${JWT_SECRET}" >> /var/www/html/.env
echo "JWT_TTL=60" >> /var/www/html/.env
echo "JWT_REFRESH_TTL=20160" >> /var/www/html/.env
echo "JWT_ALGO=HS256" >> /var/www/html/.env
echo "✅ JWT configurado completamente"

# Esperar a que PostgreSQL esté disponible
echo "⏳ Esperando a que PostgreSQL esté disponible..."
until pg_isready -h postgres_foro_academico -p 5432 -U foro_user; do
    echo "PostgreSQL no está listo - esperando..."
    sleep 2
done
echo "✅ PostgreSQL está disponible"

# Configurar permisos
echo "📁 Configurando permisos..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
php /var/www/html/artisan migrate --force --no-interaction

# Ejecutar seeders SIEMPRE
echo "🌱 Ejecutando seeders básicos..."
php /var/www/html/artisan db:seed --class=BasicDataSeeder --force --no-interaction
echo "✅ Seeders ejecutados exitosamente"

# Limpiar y cachear configuración
echo "🧹 Limpiando caché..."
php /var/www/html/artisan config:clear --no-interaction
php /var/www/html/artisan cache:clear --no-interaction
php /var/www/html/artisan route:clear --no-interaction

echo "⚡ Cacheando configuración..."
php /var/www/html/artisan config:cache --no-interaction
php /var/www/html/artisan route:cache --no-interaction

# Verificar configuración JWT
echo "� Verificando configuración JWT..."
php /var/www/html/artisan tinker --execute="echo 'JWT_TTL: ' . config('jwt.ttl') . ' (type: ' . gettype(config('jwt.ttl')) . ')' . PHP_EOL;"

echo "✅ Configuración completada exitosamente!"

# Ejecutar supervisor
echo "🏁 Iniciando supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
