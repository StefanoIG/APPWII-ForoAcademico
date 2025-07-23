@echo off
echo ========================================
echo     FORO ACADEMICO - DEPLOYMENT
echo ========================================

echo.
echo [1/3] Iniciando PostgreSQL independiente...
docker-compose -f docker-compose.postgres.yml up -d

echo.
echo [2/3] Esperando a que PostgreSQL este listo...
timeout /t 15 /nobreak >nul

echo.
echo [3/3] Iniciando aplicacion principal...
docker-compose up -d

echo.
echo ========================================
echo     DESPLIEGUE COMPLETADO
echo ========================================
echo.
echo PostgreSQL: localhost:5432 (Contenedor: postgres_standalone)
echo Aplicacion: http://localhost:8080 (Contenedor: foro_academico)
echo MailHog UI: http://localhost:8025
echo.
echo Contenedores activos:
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo.
echo Para ver logs:
echo   docker logs -f foro_academico
echo   docker logs -f postgres_standalone
echo.
echo Para ejecutar migraciones:
echo   docker exec foro_academico php artisan migrate --force
echo.
pause
