@echo off
echo ========================================
echo Testing EnviroPay Laravel Application
echo ========================================
echo.

echo 1. Clearing Laravel cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo    Cache cleared successfully!
echo.

echo 2. Creating storage link...
php artisan storage:link
echo    Storage link created!
echo.

echo 3. Checking database connection...
php artisan migrate:status
echo.

echo 4. Starting Laravel development server...
echo    Opening http://127.0.0.1:8000
echo    Press Ctrl+C to stop the server
echo.
start http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000
