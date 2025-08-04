# Foro Académico - Environment Detection & SSL Configuration

## 🔧 Sistema de Detección de Entorno

Este proyecto incluye un sistema automático de detección de entorno que configura automáticamente HTTPS/SSL cuando se ejecuta en producción.

## 🌍 Configuración de Entornos

### Variables de Entorno Clave

```bash
# Configuración de entorno
APP_ENV=local                    # local, staging, production
APP_DEBUG=true                   # false en producción
APP_FORCE_HTTPS=false           # true para forzar HTTPS

# Configuración SSL (solo producción)
SSL_CERT_PATH=/etc/nginx/ssl/cert.pem
SSL_KEY_PATH=/etc/nginx/ssl/key.pem
```

### Detección Automática

El sistema detecta automáticamente el entorno basándose en:

1. **Variable APP_ENV**: Define si es `local`, `staging` o `production`
2. **Auto-configuración**: En producción automáticamente:
   - Habilita HTTPS
   - Genera certificados SSL si no existen
   - Configura Nginx para SSL
   - Aplica headers de seguridad
   - Optimiza configuraciones

## 🚀 Instrucciones de Despliegue

### Desarrollo Local

```bash
# 1. Clonar el repositorio
git clone <repository-url>
cd foro_academico

# 2. Configurar entorno de desarrollo
cp .env.example .env

# 3. Editar .env (asegurar que APP_ENV=local)
APP_ENV=local
APP_DEBUG=true
APP_FORCE_HTTPS=false

# 4. Construir y ejecutar
docker-compose up --build
```

### Producción

```bash
# 1. Configurar entorno de producción
cp .env.example .env

# 2. Editar .env para producción
APP_ENV=production
APP_DEBUG=false
APP_FORCE_HTTPS=true
APP_URL=https://tu-dominio.com

# Configuración de base de datos
DB_CONNECTION=pgsql
DB_HOST=foro-postgres
DB_PORT=5432
DB_DATABASE=foro_academico
DB_USERNAME=foro_user
DB_PASSWORD=tu_password_seguro

# 3. (Opcional) Instalar certificados SSL propios
# Si tienes certificados válidos, cópialos a docker/ssl/
docker/ssl-manager.sh prod /path/to/your/cert.pem /path/to/your/key.pem

# 4. Desplegar
docker-compose up -d --build
```

## 🛡️ Gestión de Certificados SSL

### Script de Gestión SSL

El proyecto incluye un script de gestión de certificados SSL:

```bash
# Generar certificados de desarrollo (auto-firmados)
./docker/ssl-manager.sh dev

# Instalar certificados de producción
./docker/ssl-manager.sh prod /path/to/cert.pem /path/to/key.pem

# Verificar certificados
./docker/ssl-manager.sh verify

# Ver estado de certificados
./docker/ssl-manager.sh status

# Hacer backup de certificados
./docker/ssl-manager.sh backup

# Limpiar certificados
./docker/ssl-manager.sh clean
```

### Certificados Automáticos

Si no proporcionas certificados SSL para producción, el sistema:
1. Genera automáticamente certificados auto-firmados
2. Configura Nginx para usarlos
3. Habilita HTTPS con redirección automática

## 📁 Estructura de Configuración

```
docker/
├── ssl/                        # Certificados SSL
│   ├── cert.pem               # Certificado público
│   ├── key.pem                # Clave privada
│   └── dhparam.pem            # Parámetros DH
├── nginx/
│   ├── nginx.conf             # Configuración principal
│   ├── nginx.conf.dev         # Configuración desarrollo (HTTP)
│   └── nginx.conf.prod        # Configuración producción (HTTPS)
├── entrypoint.sh              # Script de inicialización
└── ssl-manager.sh             # Gestión de certificados
```

## 🔐 Características de Seguridad

### Desarrollo
- HTTP (puerto 80)
- Headers básicos de seguridad
- Límite de carga: 10MB

### Producción
- HTTPS (puerto 443) con redirección automática
- Certificados SSL/TLS 1.2 y 1.3
- HSTS (HTTP Strict Transport Security)
- Headers de seguridad completos:
  - X-Frame-Options
  - X-Content-Type-Options
  - X-XSS-Protection
  - Referrer-Policy
  - Content-Security-Policy
- Compresión Gzip optimizada
- Cache de archivos estáticos
- Límite de carga: 50MB
- Bloqueo de archivos sensibles

## 🚀 Funcionalidades del Sistema

### Subida de Archivos
- Imágenes: JPG, JPEG, PNG, GIF
- Documentos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
- Media: MP4, MP3, AVI, MOV
- Optimización automática de imágenes
- Límites configurables por entorno

### Markdown Avanzado
- GitHub Flavored Markdown
- Syntax highlighting
- Tablas, listas, enlaces
- Sanitización automática
- Extensiones personalizables

### API REST Completa
- Autenticación JWT
- CRUD para preguntas, respuestas, usuarios
- Sistema de votos y reputación
- Categorías y etiquetas
- Favoritos y reportes

## 📊 Monitoreo y Logs

```bash
# Ver logs del contenedor
docker-compose logs -f foro-app

# Ver logs de Nginx
docker-compose logs -f foro-nginx

# Ver logs de PostgreSQL
docker-compose logs -f foro-postgres

# Estado de certificados SSL
./docker/ssl-manager.sh status
```

## 🔧 Resolución de Problemas

### Error de Certificados SSL
```bash
# Regenerar certificados
./docker/ssl-manager.sh clean
./docker/ssl-manager.sh dev

# Reiniciar contenedores
docker-compose restart
```

### Error de Base de Datos
```bash
# Verificar conexión a PostgreSQL
docker-compose exec foro-postgres psql -U foro_user -d foro_academico

# Ejecutar migraciones manualmente
docker-compose exec foro-app php artisan migrate:fresh --seed
```

### Error de Permisos
```bash
# Corregir permisos de storage
docker-compose exec foro-app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec foro-app chmod -R 775 storage bootstrap/cache
```

## 📝 Comandos Útiles

```bash
# Reconstruir completamente
docker-compose down -v
docker-compose up --build

# Ejecutar comandos Artisan
docker-compose exec foro-app php artisan <command>

# Acceder al contenedor
docker-compose exec foro-app bash

# Ver estado de servicios
docker-compose ps

# Actualizar dependencias
docker-compose exec foro-app composer update
```

## 🏗️ Tecnologías Utilizadas

- **Backend**: Laravel 11 con PHP 8.2
- **Base de Datos**: PostgreSQL 15
- **Web Server**: Nginx con PHP-FPM
- **Contenedores**: Docker & Docker Compose
- **Autenticación**: JWT (JSON Web Tokens)
- **Procesamiento de Imágenes**: Intervention Image
- **Markdown**: League CommonMark
- **SSL/TLS**: OpenSSL con certificados automáticos

---

## 🤝 Contribuir

1. Fork el proyecto
2. Crear una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.
