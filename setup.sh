#!/bin/bash

# Script de inicialización para Foro Académico
# Este script configura el entorno Docker y prepara la aplicación

set -e

echo "🚀 Iniciando configuración de Foro Académico..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    print_error "Docker no está instalado. Por favor, instala Docker primero."
    exit 1
fi

# Verificar si Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose no está instalado. Por favor, instala Docker Compose primero."
    exit 1
fi

print_status "Verificando requisitos del sistema..."

# Crear archivo .env si no existe
if [ ! -f ".env" ]; then
    print_status "Creando archivo .env..."
    cp .env.example .env
    
    # Generar APP_KEY
    print_status "Generando APP_KEY..."
    APP_KEY=$(openssl rand -base64 32)
    sed -i "s/APP_KEY=/APP_KEY=base64:$APP_KEY/" .env
    
    # Generar JWT_SECRET
    print_status "Generando JWT_SECRET..."
    JWT_SECRET=$(openssl rand -base64 64)
    sed -i "s/JWT_SECRET=/JWT_SECRET=$JWT_SECRET/" .env
    
    # Configurar base de datos
    print_status "Configurando variables de base de datos..."
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=pgsql/' .env
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=database/' .env
    sed -i 's/DB_PORT=3306/DB_PORT=5432/' .env
    sed -i 's/DB_DATABASE=laravel/DB_DATABASE=foro_academico/' .env
    sed -i 's/DB_USERNAME=root/DB_USERNAME=foro_user/' .env
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=foro_password/' .env
    
    print_success "Archivo .env creado y configurado."
else
    print_warning "El archivo .env ya existe. Revisa la configuración manualmente."
fi

# Crear directorios necesarios
print_status "Creando directorios necesarios..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public/uploads/images
mkdir -p storage/app/public/uploads/documents
mkdir -p storage/app/public/uploads/videos
mkdir -p storage/app/public/uploads/audios
mkdir -p bootstrap/cache

# Configurar permisos (si estamos en Linux/Mac)
if [[ "$OSTYPE" == "linux-gnu"* ]] || [[ "$OSTYPE" == "darwin"* ]]; then
    print_status "Configurando permisos..."
    chmod -R 755 storage
    chmod -R 755 bootstrap/cache
fi

print_status "Construyendo contenedores Docker..."
docker-compose build --no-cache

print_status "Iniciando servicios..."
docker-compose up -d

# Esperar a que la base de datos esté lista
print_status "Esperando a que la base de datos esté lista..."
sleep 30

# Ejecutar migraciones y seeders
print_status "Ejecutando migraciones..."
docker-compose exec backend php artisan migrate --force

print_status "Ejecutando seeders..."
docker-compose exec backend php artisan db:seed --force

# Crear enlace simbólico para storage
print_status "Configurando storage..."
docker-compose exec backend php artisan storage:link

# Limpiar cache
print_status "Limpiando cache..."
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache

print_success "¡Configuración completada!"
echo ""
echo "🌟 Foro Académico está listo!"
echo ""
echo "📊 Servicios disponibles:"
echo "   • Backend API: http://localhost:8080"
echo "   • Base de datos: localhost:5432"
echo "   • MailHog UI: http://localhost:8025"
echo ""
echo "🔧 Comandos útiles:"
echo "   • Ver logs: docker-compose logs -f"
echo "   • Parar servicios: docker-compose down"
echo "   • Reiniciar: docker-compose restart"
echo "   • Acceder al backend: docker-compose exec backend bash"
echo ""
echo "📖 Documentación de API disponible en: http://localhost:8080/api/public/categories"
echo ""
print_success "¡Disfruta desarrollando! 🚀"
