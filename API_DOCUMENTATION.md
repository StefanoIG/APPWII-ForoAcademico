# 🎓 Foro Académico API

Sistema de foro académico completo desarrollado en Laravel con autenticación JWT, sistema de votos, reportes y gestión de contenido.

## 🚀 Funcionalidades Implementadas

### ✅ Sistema de Autenticación
- **JWT Authentication** configurado y funcionando
- Registro de usuarios con validaciones
- Login/Logout con tokens JWT
- Refresh de tokens
- Middleware de autenticación

### ✅ Gestión de Usuarios
- **Roles**: `usuario`, `moderador`, `admin`
- **Sistema de reputación** basado en votos
- Perfiles de usuario con estadísticas
- Leaderboard de usuarios
- CRUD completo de usuarios (admin)

### ✅ Sistema de Preguntas y Respuestas
- **CRUD completo de preguntas** con validaciones
- **CRUD completo de respuestas** con validaciones
- Una respuesta por pregunta por usuario
- **Marcar mejor respuesta** (solo autor de pregunta)
- Estados de preguntas: `abierta`, `resuelta`, `cerrada`
- **Sistema de favoritos**

### ✅ Sistema de Votos
- **Votos en preguntas y respuestas** (+1 / -1)
- **Sistema de reputación automático**:
  - +5 puntos por voto positivo
  - -2 puntos por voto negativo
  - +10 puntos por mejor respuesta
- Eventos y Listeners para actualización de reputación
- Prevención de auto-votos

### ✅ Categorías y Etiquetas
- **CRUD de categorías** (solo admin)
- **CRUD de etiquetas** (solo admin)
- Relaciones many-to-many entre preguntas y etiquetas
- Estadísticas de popularidad

### ✅ Sistema de Reportes
- **Reportar contenido ofensivo**
- Tipos: `spam`, `ofensivo`, `inapropiado`, `otro`
- Estados: `pendiente`, `revisado`, `descartado`
- Panel de moderación para admin/moderadores

### ✅ Dashboard y Estadísticas
- **Dashboard administrativo** con estadísticas completas
- **Estadísticas públicas** del foro
- **Actividad del usuario** individual
- Gráficos de actividad mensual

## 🛠️ Tecnologías Utilizadas

- **Laravel 11**
- **JWT Auth (tymon/jwt-auth)**
- **SQLite** (configurado)
- **Eloquent ORM** con relaciones polimórficas
- **Request Validation** con Form Requests
- **Events & Listeners** para el sistema de reputación
- **Middleware personalizado** para roles
- **API REST** completa

## 📋 Instalación y Configuración

### 1. Clonar y configurar dependencias
```bash
composer install
npm install
```

### 2. Configurar base de datos
El proyecto está configurado para usar SQLite. La base de datos ya está creada en `database/database.sqlite`.

### 3. Configurar JWT
```bash
php artisan jwt:secret
```

### 4. Ejecutar migraciones y seeders
```bash
php artisan migrate
php artisan db:seed --class=BasicDataSeeder
```

### 5. Usuarios de prueba creados
- **Admin**: `admin@foro.com` / `password`
- **Moderador**: `moderador@foro.com` / `password`
- **Usuario**: `juan@example.com` / `password`
- **Usuario**: `maria@example.com` / `password`

## 🔗 Endpoints de la API

### 🔐 Autenticación
```
POST /api/auth/register     - Registrar usuario
POST /api/auth/login        - Iniciar sesión
POST /api/auth/logout       - Cerrar sesión
POST /api/auth/refresh      - Renovar token
GET  /api/auth/me           - Datos del usuario autenticado
```

### 👥 Usuarios
```
GET  /api/profile                    - Perfil del usuario autenticado
GET  /api/users                      - Lista de usuarios (admin)
GET  /api/users/{user}               - Ver perfil de usuario
PUT  /api/users/{user}               - Actualizar usuario
DELETE /api/users/{user}             - Eliminar usuario (admin)
GET  /api/users/{user}/questions     - Preguntas del usuario
GET  /api/users/{user}/answers       - Respuestas del usuario
GET  /api/users/leaderboard          - Ranking de usuarios
```

### ❓ Preguntas
```
GET  /api/questions                     - Listar preguntas (con filtros)
POST /api/questions                     - Crear pregunta
GET  /api/questions/{question}          - Ver pregunta
PUT  /api/questions/{question}          - Actualizar pregunta
DELETE /api/questions/{question}        - Eliminar pregunta
POST /api/questions/{question}/favorite - Agregar a favoritos
DELETE /api/questions/{question}/favorite - Quitar de favoritos
GET  /api/my/favorites                  - Mis preguntas favoritas
```

### 💬 Respuestas
```
GET  /api/answers                          - Listar respuestas
POST /api/answers                          - Crear respuesta
GET  /api/answers/{answer}                 - Ver respuesta
PUT  /api/answers/{answer}                 - Actualizar respuesta
DELETE /api/answers/{answer}               - Eliminar respuesta
POST /api/answers/{answer}/mark-as-best    - Marcar como mejor respuesta
```

### 👍 Votos
```
GET  /api/votes                    - Listar votos
POST /api/votes                    - Crear/actualizar voto
GET  /api/votes/{vote}             - Ver voto
PUT  /api/votes/{vote}             - Actualizar voto
DELETE /api/votes/{vote}           - Eliminar voto
GET  /api/vote-stats               - Estadísticas de votos
POST /api/questions/{question}/vote - Votar pregunta (legacy)
```

### 🚨 Reportes
```
GET  /api/reports                - Listar reportes (admin/moderador)
POST /api/reports                - Crear reporte
GET  /api/reports/{report}       - Ver reporte
PUT  /api/reports/{report}       - Actualizar estado (admin/moderador)
DELETE /api/reports/{report}     - Eliminar reporte (admin)
GET  /api/reports/statistics     - Estadísticas de reportes
```

### 📚 Categorías
```
GET  /api/public/categories                  - Listar categorías
GET  /api/public/categories/{category}       - Ver categoría
GET  /api/categories/{category}/questions    - Preguntas de categoría
POST /api/categories                         - Crear categoría (admin)
PUT  /api/categories/{category}              - Actualizar categoría (admin)
DELETE /api/categories/{category}            - Eliminar categoría (admin)
```

### 🏷️ Etiquetas
```
GET  /api/public/tags                  - Listar etiquetas
GET  /api/public/tags/{tag}            - Ver etiqueta
GET  /api/public/tags/popular          - Etiquetas populares
GET  /api/tags/{tag}/questions         - Preguntas con etiqueta
POST /api/tags                         - Crear etiqueta (admin)
PUT  /api/tags/{tag}                   - Actualizar etiqueta (admin)
DELETE /api/tags/{tag}                 - Eliminar etiqueta (admin)
```

### 📊 Dashboard y Estadísticas
```
GET  /api/public/stats              - Estadísticas públicas
GET  /api/dashboard/admin-stats     - Dashboard administrativo (admin)
GET  /api/dashboard/user-activity   - Actividad del usuario
```

## 🔒 Sistema de Permisos

### Usuario (`usuario`)
- Crear, editar y eliminar sus propias preguntas
- Crear, editar y eliminar sus propias respuestas
- Votar contenido de otros usuarios
- Marcar respuestas como mejores en sus preguntas
- Reportar contenido
- Gestionar sus favoritos

### Moderador (`moderador`)
- Todo lo de usuario
- Ver y gestionar reportes
- Ver estadísticas de reportes

### Administrador (`admin`)
- Todo lo de moderador
- Gestionar usuarios (CRUD)
- Gestionar categorías y etiquetas
- Ver dashboard administrativo completo
- Eliminar cualquier contenido

## 🎯 Reglas de Negocio Implementadas

✅ **Solo usuarios registrados** pueden crear preguntas o respuestas  
✅ **Una respuesta por pregunta** por usuario (puede editarla)  
✅ **Mínimo 30 caracteres** en respuestas  
✅ **Al menos una etiqueta** por pregunta  
✅ **No auto-votos** - usuarios no pueden votar su propio contenido  
✅ **Sistema de reputación automático** con eventos  
✅ **Estados de preguntas** manejados correctamente  
✅ **Validaciones completas** en todos los endpoints  
✅ **Middleware de autorización** para diferentes roles  

## 🚀 Próximos Pasos Sugeridos

1. **Frontend**: Crear interfaz con React/Vue/Angular
2. **Notificaciones**: Sistema de notificaciones en tiempo real
3. **Búsqueda**: Implementar búsqueda avanzada con Elasticsearch
4. **Cache**: Agregar cache con Redis
5. **Testing**: Implementar tests unitarios y de integración
6. **API Documentation**: Generar documentación con Swagger/OpenAPI

## 🤝 Contribuir

El proyecto está listo para desarrollo. Todos los controladores, modelos, migraciones y rutas están implementados y funcionando.

---

**¡El sistema está completamente funcional y listo para usar!** 🎉
