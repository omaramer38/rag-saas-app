@echo off
echo ============================================================
echo   DoctorChat - Start All Services
echo ============================================================
echo.

REM Start RAG Server
echo Starting RAG Server on port 5000...
cd /d "%~dp0rag-service"
start /B python multi_tenant_server_local.py > rag_server.log 2>&1
timeout /t 5 /nobreak > nul
echo RAG Server started!

REM Start Laravel Server
echo Starting Laravel Server on port 8000...
cd /d "%~dp0"
start /B php artisan serve --host=127.0.0.1 --port=8000 > laravel_server.log 2>&1
timeout /t 3 /nobreak > nul
echo Laravel Server started!

echo.
echo ============================================================
echo   All services are running!
echo   ============================================================
echo   Laravel:  http://127.0.0.1:8000
echo   RAG API:  http://localhost:5000
echo   Qdrant:   embedded (no Docker needed)
echo ============================================================
echo.
echo Press any key to stop all services...
pause > nul

REM Stop services
echo Stopping services...
taskkill /F /FI "WINDOWTITLE eq rag_server*" 2>nul
taskkill /F /FI "WINDOWTITLE eq laravel_server*" 2>nul
echo Done!
