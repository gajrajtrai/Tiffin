@echo off
cd /d C:\Users\RAIPC\tiffin-app
call npm run build
call php artisan optimize
php artisan serve --host=0.0.0.0 --port=80