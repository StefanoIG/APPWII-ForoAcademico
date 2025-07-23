@echo off
echo ========================================
echo     FORO ACADEMICO - PARAR SERVICIOS
echo ========================================

echo.
echo [1/2] Parando aplicacion principal...
docker-compose down

echo.
echo [2/2] Parando PostgreSQL...
docker-compose -f docker-compose.postgres.yml down

echo.
echo ========================================
echo     SERVICIOS DETENIDOS
echo ========================================
pause
