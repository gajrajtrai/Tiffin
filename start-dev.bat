@echo off
cd /d C:\Users\RAIPC\tiffin-app
start "Vite" cmd /k "npm run dev"
php artisan serve --host=0.0.0.0 --port=8000