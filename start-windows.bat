@echo off
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (
 echo PHP was not found in PATH.
 echo Install PHP 8.1+ or use XAMPP and browse to this folder via Apache.
 pause
 exit /b 1
)
echo Quran OBS Studio is running on http://127.0.0.1:8765
start "Quran OBS Control Panel" "http://127.0.0.1:8765/control-panel.html"
php -S 127.0.0.1:8765
pause
