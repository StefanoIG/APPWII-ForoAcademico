#!/bin/bash

# Script de health check para el contenedor backend
# Verifica que tanto Nginx como PHP-FPM estén funcionando

set -e

# Verificar que Nginx esté respondiendo
nginx_status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/public/categories || echo "000")

if [ "$nginx_status" != "200" ]; then
    echo "❌ Nginx no está respondiendo correctamente (HTTP $nginx_status)"
    exit 1
fi

# Verificar que PHP-FPM esté activo
if ! pgrep -x "php-fpm" > /dev/null; then
    echo "❌ PHP-FPM no está ejecutándose"
    exit 1
fi

# Verificar que el supervisor esté activo
if ! pgrep -x "supervisord" > /dev/null; then
    echo "❌ Supervisor no está ejecutándose"
    exit 1
fi

# Verificar conexión a base de datos
db_check=$(php -r "
try {
    \$pdo = new PDO('pgsql:host=database;dbname=foro_academico', 'foro_user', 'foro_password');
    echo 'OK';
} catch (Exception \$e) {
    echo 'ERROR';
}
" 2>/dev/null || echo "ERROR")

if [ "$db_check" != "OK" ]; then
    echo "❌ No se puede conectar a la base de datos"
    exit 1
fi

echo "✅ Todos los servicios están funcionando correctamente"
exit 0
