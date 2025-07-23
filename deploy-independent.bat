@echo off
echo ========================================
echo     FORO ACADEMICO - DEPLOYMENT
echo ========================================

echo.
echo [1/3] Iniciando PostgreSQL independiente...
cd /d C:\postgres-foro
docker-compose up -d
cd /d C:\projects\foro_academico

echo.
echo [2/3] Esperando a que PostgreSQL este listo...
timeout /t 10 /nobreak >nul

echo.
echo [3/3] Iniciando aplicacion principal...
docker-compose up -d

echo.
echo ========================================
echo     DESPLIEGUE COMPLETADO
echo ========================================
echo.
echo CONTENEDORES INDEPENDIENTES:
echo.
echo PostgreSQL: localhost:5432 (Contenedor: postgres_foro_academico)
echo Aplicacion: http://localhost:8080 (Contenedor: foro_academico)
echo MailHog UI: http://localhost:8025 (Contenedor: foro_academico_mailhog)
echo.
echo Contenedores activos:
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo.
echo Para ver logs:
echo   docker-compose logs -f foro_academico
echo   cd C:\postgres-foro ^&^& docker-compose logs -f postgres
echo.
echo Para ejecutar migraciones:
echo   docker-compose exec foro_academico php artisan migrate --force
echo.
echo Para parar PostgreSQL independiente:
echo   cd C:\postgres-foro ^&^& docker-compose down
echo.
pause
