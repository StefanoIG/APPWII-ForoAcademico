# Script de inicialización para Foro Académico en Windows
# Este script configura el entorno Docker y prepara la aplicación

param(
    [switch]$Force,
    [switch]$SkipBuild
)

# Función para mostrar mensajes con colores
function Write-Status {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Blue
}

function Write-Success {
    param([string]$Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

Write-Host "Iniciando configuracion de Foro Academico..." -ForegroundColor Cyan

# Verificar si Docker está instalado
try {
    docker --version | Out-Null
    Write-Status "Docker encontrado."
} catch {
    Write-Error "Docker no está instalado. Por favor, instala Docker Desktop primero."
    exit 1
}

# Verificar si Docker Compose está disponible
try {
    docker-compose --version | Out-Null
    Write-Status "Docker Compose encontrado."
} catch {
    Write-Error "Docker Compose no está disponible. Por favor, instala Docker Desktop con Compose."
    exit 1
}

# Crear archivo .env si no existe o si se fuerza
if (!(Test-Path ".env") -or $Force) {
    Write-Status "Creando archivo .env..."
    
    if (Test-Path ".env.docker") {
        Copy-Item ".env.docker" ".env"
    } else {
        Write-Error "Archivo .env.docker no encontrado. Creando uno básico..."
        @"
APP_NAME="Foro Académico"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
DB_DATABASE=foro_academico
DB_USERNAME=foro_user
DB_PASSWORD=foro_password

JWT_SECRET=
"@ | Out-File -FilePath ".env" -Encoding UTF8
    }
    
    # Generar APP_KEY
    Write-Status "Generando APP_KEY..."
    $appKey = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes((New-Guid).ToString() + (New-Guid).ToString()))
    (Get-Content ".env") -replace "APP_KEY=", "APP_KEY=base64:$appKey" | Set-Content ".env"
    
    # Generar JWT_SECRET
    Write-Status "Generando JWT_SECRET..."
    $jwtSecret = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes((New-Guid).ToString() + (New-Guid).ToString() + (New-Guid).ToString()))
    (Get-Content ".env") -replace "JWT_SECRET=", "JWT_SECRET=$jwtSecret" | Set-Content ".env"
    
    Write-Success "Archivo .env creado y configurado."
} else {
    Write-Warning "El archivo .env ya existe. Usa -Force para recrearlo."
}

# Crear directorios necesarios
Write-Status "Creando directorios necesarios..."
$directories = @(
    "storage\logs",
    "storage\framework\cache",
    "storage\framework\sessions", 
    "storage\framework\views",
    "storage\app\public\uploads\images",
    "storage\app\public\uploads\documents", 
    "storage\app\public\uploads\videos",
    "storage\app\public\uploads\audios",
    "bootstrap\cache"
)

foreach ($dir in $directories) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# Construir contenedores Docker
if (!$SkipBuild) {
    Write-Status "Construyendo contenedores Docker..."
    docker-compose build --no-cache
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Error al construir los contenedores."
        exit 1
    }
}

Write-Status "Iniciando servicios..."
docker-compose up -d
if ($LASTEXITCODE -ne 0) {
    Write-Error "Error al iniciar los servicios."
    exit 1
}

# Esperar a que la base de datos esté lista
Write-Status "Esperando a que la base de datos esté lista..."
Start-Sleep -Seconds 30

# Verificar que los contenedores estén corriendo
Write-Status "Verificando estado de los contenedores..."
docker-compose ps

# Ejecutar migraciones y seeders
Write-Status "Ejecutando migraciones..."
docker-compose exec -T backend php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Warning "Algunas migraciones pueden haber fallado. Revisar logs."
}

Write-Status "Ejecutando seeders..."
docker-compose exec -T backend php artisan db:seed --force
if ($LASTEXITCODE -ne 0) {
    Write-Warning "Algunos seeders pueden haber fallado. Revisar logs."
}

# Crear enlace simbólico para storage
Write-Status "Configurando storage..."
docker-compose exec -T backend php artisan storage:link

# Limpiar cache
Write-Status "Limpiando cache..."
docker-compose exec -T backend php artisan config:cache
docker-compose exec -T backend php artisan route:cache

Write-Success "¡Configuración completada!"
Write-Host ""
Write-Host "Foro Academico esta listo!" -ForegroundColor Cyan
Write-Host ""
Write-Host "Servicios disponibles:" -ForegroundColor White
Write-Host "   • Backend API: http://localhost:8080" -ForegroundColor Gray
Write-Host "   • Base de datos: localhost:5432" -ForegroundColor Gray
Write-Host "   • MailHog UI: http://localhost:8025" -ForegroundColor Gray
Write-Host ""
Write-Host "Comandos utiles:" -ForegroundColor White
Write-Host "   • Ver logs: docker-compose logs -f" -ForegroundColor Gray
Write-Host "   • Parar servicios: docker-compose down" -ForegroundColor Gray
Write-Host "   • Reiniciar: docker-compose restart" -ForegroundColor Gray
Write-Host "   • Acceder al backend: docker-compose exec backend bash" -ForegroundColor Gray
Write-Host ""
Write-Host "Documentacion de API disponible en: http://localhost:8080/api/public/categories" -ForegroundColor Gray
Write-Host ""
Write-Success "Disfruta desarrollando!"
