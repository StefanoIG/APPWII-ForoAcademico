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

# PASO 3: Detectar entorno y configurar SSL/HTTPS si es necesario
echo "🌍 Detectando entorno de aplicación..."
APP_ENV=$(grep "^APP_ENV=" /var/www/html/.env | cut -d'=' -f2 | tr -d '"')

if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "prod" ]; then
    echo "🏭 Entorno de PRODUCCIÓN detectado"
    echo "🔒 Configurando HTTPS y SSL..."
    
    # Verificar si existen certificados SSL
    if [ -f "/etc/ssl/certs/localhost.crt" ] && [ -f "/etc/ssl/private/localhost.key" ]; then
        echo "✅ Certificados SSL encontrados"
        
        # Usar configuración de Nginx para producción
        if [ -f "/etc/nginx/nginx.conf.prod" ]; then
            echo "🔄 Aplicando configuración de Nginx para producción..."
            cp /etc/nginx/nginx.conf.prod /etc/nginx/nginx.conf
        fi
        
        # Actualizar APP_URL a HTTPS si no está configurado
        if grep -q "APP_URL=http://" /var/www/html/.env; then
            sed -i 's|APP_URL=http://|APP_URL=https://|g' /var/www/html/.env
            echo "🔗 APP_URL actualizada a HTTPS"
        fi
        
        # Configurar forzar HTTPS
        if ! grep -q "APP_FORCE_HTTPS=" /var/www/html/.env; then
            echo "APP_FORCE_HTTPS=true" >> /var/www/html/.env
        else
            sed -i 's/APP_FORCE_HTTPS=.*/APP_FORCE_HTTPS=true/' /var/www/html/.env
        fi
        
        echo "✅ Configuración HTTPS completada"
    else
        echo "⚠️ Certificados SSL no encontrados. Generando certificados auto-firmados..."
        
        # Crear directorios si no existen
        mkdir -p /etc/ssl/certs /etc/ssl/private
        
        # Generar certificados auto-firmados
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout /etc/ssl/private/localhost.key \
            -out /etc/ssl/certs/localhost.crt \
            -subj "/C=US/ST=Local/L=Local/O=Development/CN=localhost"
        
        chmod 600 /etc/ssl/private/localhost.key
        chmod 644 /etc/ssl/certs/localhost.crt
        
        echo "✅ Certificados SSL auto-firmados generados"
        
        # Aplicar configuración de producción
        if [ -f "/etc/nginx/nginx.conf.prod" ]; then
            cp /etc/nginx/nginx.conf.prod /etc/nginx/nginx.conf
        fi
    fi
    
    # Configurar headers de seguridad para producción
    echo "🛡️ Configurando headers de seguridad..."
    if ! grep -q "SECURE_HEADERS=" /var/www/html/.env; then
        echo "SECURE_HEADERS=true" >> /var/www/html/.env
    fi
    
else
    echo "🏠 Entorno de DESARROLLO detectado"
    echo "🔓 Configurando HTTP (desarrollo)..."
    
    # Usar configuración de Nginx para desarrollo
    if [ -f "/etc/nginx/nginx.conf.dev" ]; then
        echo "🔄 Aplicando configuración de Nginx para desarrollo..."
        cp /etc/nginx/nginx.conf.dev /etc/nginx/nginx.conf
    fi
    
    # Asegurar que APP_URL esté en HTTP para desarrollo
    if grep -q "APP_URL=https://" /var/www/html/.env; then
        sed -i 's|APP_URL=https://|APP_URL=http://|g' /var/www/html/.env
        echo "🔗 APP_URL mantenida en HTTP para desarrollo"
    fi
    
    # Configurar no forzar HTTPS en desarrollo
    if ! grep -q "APP_FORCE_HTTPS=" /var/www/html/.env; then
        echo "APP_FORCE_HTTPS=false" >> /var/www/html/.env
    else
        sed -i 's/APP_FORCE_HTTPS=.*/APP_FORCE_HTTPS=false/' /var/www/html/.env
    fi
    
    echo "✅ Configuración HTTP para desarrollo completada"
fi

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

# Crear enlace de storage
echo "🔗 Configurando enlace de storage..."
if [ -L "/var/www/html/public/storage" ]; then
    echo "Eliminando enlace de storage existente..."
    rm -f /var/www/html/public/storage
elif [ -d "/var/www/html/public/storage" ]; then
    echo "Verificando si es un directorio vacío o un punto de montaje..."
    if mountpoint -q /var/www/html/public/storage; then
        echo "⚠️ /var/www/html/public/storage es un punto de montaje. Omitiendo..."
    else
        echo "Eliminando directorio de storage existente..."
        rm -rf /var/www/html/public/storage
    fi
fi

# Crear el enlace simbólico solo si no existe ya
if [ ! -e "/var/www/html/public/storage" ]; then
    ln -sf /var/www/html/storage/app/public /var/www/html/public/storage
    echo "✅ Enlace de storage creado exitosamente"
else
    echo "✅ Enlace de storage ya existe"
fi

# Asegurar que el directorio uploads existe
mkdir -p /var/www/html/storage/app/public/uploads/images
mkdir -p /var/www/html/storage/app/public/uploads/documents
mkdir -p /var/www/html/storage/app/public/uploads/videos
mkdir -p /var/www/html/storage/app/public/uploads/audios
chown -R www-data:www-data /var/www/html/storage/app/public
echo "✅ Directorios de uploads creados"

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
