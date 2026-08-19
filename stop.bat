@echo off
echo Stopping all services...

REM Stop Python processes (RAG Server)
taskkill /F /IM python.exe /FI "WINDOWTITLE eq multi_tenant*" 2>nul

REM Find and kill the RAG server process
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":5000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a 2>nul
)

REM Find and kill the Laravel server process
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a 2>nul
)

echo All services stopped!
