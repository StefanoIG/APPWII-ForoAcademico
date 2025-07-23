# Makefile para Foro Académico
# Comandos útiles para desarrollo con Docker

.PHONY: help build up down restart logs clean install migrate seed fresh test shell db-shell

# Variables
DOCKER_COMPOSE = docker-compose
BACKEND_CONTAINER = foro_academico_backend
DB_CONTAINER = foro_academico_db

# Comando por defecto
help: ## Mostrar esta ayuda
	@echo "Foro Académico - Comandos disponibles:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Comandos de construcción e inicialización
build: ## Construir los contenedores
	$(DOCKER_COMPOSE) build --no-cache

up: ## Iniciar todos los servicios
	$(DOCKER_COMPOSE) up -d

down: ## Detener todos los servicios
	$(DOCKER_COMPOSE) down

restart: ## Reiniciar todos los servicios
	$(DOCKER_COMPOSE) restart

# Comandos de logs y monitoreo
logs: ## Ver logs de todos los servicios
	$(DOCKER_COMPOSE) logs -f

logs-backend: ## Ver logs solo del backend
	$(DOCKER_COMPOSE) logs -f backend

logs-db: ## Ver logs solo de la base de datos
	$(DOCKER_COMPOSE) logs -f database

# Comandos de desarrollo
shell: ## Acceder al shell del backend
	$(DOCKER_COMPOSE) exec backend bash

db-shell: ## Acceder al shell de PostgreSQL
	$(DOCKER_COMPOSE) exec database psql -U foro_user -d foro_academico

# Comandos de Laravel
install: ## Instalar dependencias de Composer
	$(DOCKER_COMPOSE) exec backend composer install

migrate: ## Ejecutar migraciones
	$(DOCKER_COMPOSE) exec backend php artisan migrate

migrate-fresh: ## Ejecutar migraciones desde cero
	$(DOCKER_COMPOSE) exec backend php artisan migrate:fresh

seed: ## Ejecutar seeders
	$(DOCKER_COMPOSE) exec backend php artisan db:seed

fresh: ## Migrar desde cero y ejecutar seeders
	$(DOCKER_COMPOSE) exec backend php artisan migrate:fresh --seed

# Comandos de cache
cache-clear: ## Limpiar toda la cache
	$(DOCKER_COMPOSE) exec backend php artisan cache:clear
	$(DOCKER_COMPOSE) exec backend php artisan config:clear
	$(DOCKER_COMPOSE) exec backend php artisan route:clear
	$(DOCKER_COMPOSE) exec backend php artisan view:clear

cache-optimize: ## Optimizar cache para producción
	$(DOCKER_COMPOSE) exec backend php artisan config:cache
	$(DOCKER_COMPOSE) exec backend php artisan route:cache
	$(DOCKER_COMPOSE) exec backend php artisan view:cache

# Comandos de testing
test: ## Ejecutar tests
	$(DOCKER_COMPOSE) exec backend php artisan test

test-coverage: ## Ejecutar tests con cobertura
	$(DOCKER_COMPOSE) exec backend php artisan test --coverage

# Comandos de limpieza
clean: ## Limpiar contenedores, imágenes y volúmenes
	$(DOCKER_COMPOSE) down -v --rmi all --remove-orphans
	docker system prune -f

clean-volumes: ## Eliminar solo los volúmenes
	$(DOCKER_COMPOSE) down -v

# Comandos de mantenimiento
backup-db: ## Crear backup de la base de datos
	mkdir -p backups
	$(DOCKER_COMPOSE) exec database pg_dump -U foro_user foro_academico > backups/backup_$(shell date +%Y%m%d_%H%M%S).sql

restore-db: ## Restaurar base de datos (usar: make restore-db FILE=backup.sql)
	$(DOCKER_COMPOSE) exec -T database psql -U foro_user -d foro_academico < $(FILE)

# Comandos de desarrollo específicos
jwt-secret: ## Generar nueva clave JWT
	$(DOCKER_COMPOSE) exec backend php artisan jwt:secret --force

storage-link: ## Crear enlace simbólico para storage
	$(DOCKER_COMPOSE) exec backend php artisan storage:link

queue-work: ## Ejecutar worker de colas
	$(DOCKER_COMPOSE) exec backend php artisan queue:work

queue-restart: ## Reiniciar workers de colas
	$(DOCKER_COMPOSE) exec backend php artisan queue:restart

# Comandos de información
status: ## Mostrar estado de los contenedores
	$(DOCKER_COMPOSE) ps

info: ## Mostrar información del proyecto
	@echo "Foro Académico - Información del proyecto"
	@echo "========================================="
	@echo "Backend URL: http://localhost:8080"
	@echo "Database: localhost:5432"
	@echo "MailHog UI: http://localhost:8025"
	@echo ""
	@echo "Contenedores:"
	@$(DOCKER_COMPOSE) ps

# Comandos de setup inicial
setup: build up migrate seed storage-link cache-optimize ## Setup completo del proyecto
	@echo "🎉 Proyecto configurado exitosamente!"
	@echo "Backend disponible en: http://localhost:8080"

# Comando para desarrollo diario
dev: up logs ## Iniciar entorno de desarrollo y mostrar logs
