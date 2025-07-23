# 🐳 Foro Académico - Guía de Docker

Este documento describe cómo ejecutar el Foro Académico usando Docker con PostgreSQL como base de datos.

## 📋 Requisitos Previos

- **Docker Desktop** (Windows/Mac) o **Docker Engine** (Linux)
- **Docker Compose** v2.0+
- **Git** para clonar el repositorio
- Al menos **4GB de RAM** disponible
- **Puertos disponibles**: 8080, 5432, 8025

## 🚀 Inicio Rápido

### Opción 1: Script Automático (Recomendado)

**Windows (PowerShell):**
```powershell
.\setup.ps1
```

**Linux/Mac (Bash):**
```bash
chmod +x setup.sh
./setup.sh
```

### Opción 2: Usando Makefile

```bash
# Setup completo
make setup

# O paso a paso
make build
make up
make migrate
make seed
```

### Opción 3: Manual

1. **Clonar y configurar:**
```bash
git clone <repository-url>
cd foro_academico
cp .env.docker .env
```

2. **Generar claves:**
```bash
# Generar APP_KEY (reemplazar en .env)
openssl rand -base64 32

# Generar JWT_SECRET (reemplazar en .env)
openssl rand -base64 64
```

3. **Construir y ejecutar:**
```bash
docker-compose build
docker-compose up -d
```

4. **Configurar aplicación:**
```bash
docker-compose exec backend php artisan migrate --force
docker-compose exec backend php artisan db:seed --force
docker-compose exec backend php artisan storage:link
```

## 🏗️ Arquitectura del Sistema

### Contenedores

| Servicio | Contenedor | Puerto | Descripción |
|----------|------------|--------|-------------|
| **Backend** | `foro_academico_backend` | 8080 | Laravel + Nginx + PHP-FPM |
| **Database** | `foro_academico_db` | 5432 | PostgreSQL 15 |
| **Mail** | `foro_academico_mailhog` | 8025 | MailHog para testing |

### Tecnologías

- **Backend**: PHP 8.2, Laravel 11, Nginx
- **Base de Datos**: PostgreSQL 15 con extensiones
- **Cache**: Database driver (configurable)
- **Colas**: Database driver con supervisor
- **Storage**: Local filesystem con optimización

## 🔧 Configuración

### Variables de Entorno

Las principales variables en `.env`:

```bash
# Aplicación
APP_NAME="Foro Académico"
APP_URL=http://localhost:8080

# Base de Datos
DB_CONNECTION=pgsql
DB_HOST=database
DB_DATABASE=foro_academico
DB_USERNAME=foro_user
DB_PASSWORD=foro_password

# JWT
JWT_SECRET=<your-jwt-secret>
JWT_TTL=1440

# Archivos
MAX_UPLOAD_SIZE=10240
ALLOWED_FILE_TYPES="jpeg,jpg,png,gif,pdf,doc,docx,txt,mp4,mp3"
```

### Volúmenes Persistentes

- **Base de datos**: `postgres_data` - Datos de PostgreSQL
- **Storage**: `./storage` - Archivos subidos y logs
- **Cache**: `./bootstrap/cache` - Cache de Laravel

## 📁 Estructura de Archivos Docker

```
docker/
├── nginx/
│   └── default.conf         # Configuración Nginx
├── postgres/
│   └── init.sql            # Inicialización PostgreSQL
└── supervisor/
    └── supervisord.conf    # Configuración Supervisor

Dockerfile                  # Imagen del backend
docker-compose.yml         # Orquestación de servicios
.dockerignore              # Archivos excluidos
.env.docker                # Plantilla de variables
setup.sh                  # Script de instalación (Linux/Mac)
setup.ps1                 # Script de instalación (Windows)
Makefile                  # Comandos de desarrollo
```

## 🛠️ Comandos Útiles

### Gestión de Contenedores

```bash
# Ver estado
docker-compose ps

# Ver logs
docker-compose logs -f
docker-compose logs -f backend
docker-compose logs -f database

# Reiniciar servicios
docker-compose restart
docker-compose restart backend

# Detener todo
docker-compose down

# Detener y eliminar volúmenes
docker-compose down -v
```

### Acceso a Contenedores

```bash
# Shell del backend
docker-compose exec backend bash

# Shell de PostgreSQL
docker-compose exec database psql -U foro_user -d foro_academico

# Ejecutar comandos de Artisan
docker-compose exec backend php artisan <command>
```

### Mantenimiento de Laravel

```bash
# Migraciones
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan migrate:fresh --seed

# Cache
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:cache

# Storage
docker-compose exec backend php artisan storage:link

# Colas
docker-compose exec backend php artisan queue:work
```

## 🔍 Endpoints de la API

Una vez iniciado, la API estará disponible en:

### Públicos
- **Base**: http://localhost:8080/api/public
- **Categorías**: http://localhost:8080/api/public/categories
- **Preguntas**: http://localhost:8080/api/public/questions

### Autenticación
- **Login**: POST http://localhost:8080/api/auth/login
- **Registro**: POST http://localhost:8080/api/auth/register

### Herramientas
- **MailHog**: http://localhost:8025

## 🐛 Solución de Problemas

### Contenedor no inicia

```bash
# Ver logs detallados
docker-compose logs backend

# Reconstruir imagen
docker-compose build --no-cache backend
```

### Base de datos no conecta

```bash
# Verificar estado de PostgreSQL
docker-compose exec database pg_isready -U foro_user

# Ver logs de base de datos
docker-compose logs database

# Reiniciar solo la base de datos
docker-compose restart database
```

### Problemas de permisos

```bash
# En Linux/Mac - corregir permisos
sudo chown -R $USER:$USER storage bootstrap/cache

# En el contenedor
docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

### Puerto ya en uso

```bash
# Cambiar puerto en docker-compose.yml
ports:
  - "8081:80"  # Cambiar de 8080 a 8081
```

### Reset completo

```bash
# Eliminar todo y empezar de nuevo
docker-compose down -v --rmi all
docker system prune -f
make setup
```

## 📊 Monitoreo y Logs

### Estructura de Logs

```
storage/logs/
├── laravel.log           # Logs de aplicación
├── worker.log           # Logs de colas
└── nginx/
    ├── access.log       # Accesos Nginx
    └── error.log        # Errores Nginx
```

### Comandos de Monitoreo

```bash
# Logs en tiempo real
docker-compose logs -f --tail=100

# Uso de recursos
docker stats

# Estado de salud
docker-compose exec backend curl -f http://localhost/api/public/categories
```

## 🚀 Despliegue en Producción

### Consideraciones

1. **Variables de entorno**:
   - Cambiar `APP_ENV=production`
   - Establecer `APP_DEBUG=false`
   - Configurar `APP_KEY` y `JWT_SECRET` únicos

2. **Base de datos**:
   - Usar base de datos externa o configurar backups
   - Configurar replicación si es necesario

3. **Storage**:
   - Considerar S3 o similar para archivos
   - Configurar CDN para contenido estático

4. **Proxy reverso**:
   - Configurar Nginx/Apache como proxy
   - Habilitar SSL/TLS

### Docker Compose para Producción

```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  backend:
    build: .
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/html/storage:cached
    restart: unless-stopped
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.

## 📞 Soporte

- **Documentación**: Este README
- **Issues**: GitHub Issues
- **Logs**: `docker-compose logs`

---

¡Disfruta desarrollando con el Foro Académico! 🚀
