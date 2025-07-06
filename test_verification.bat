@echo off
echo =========================================
echo Testing Verifikasi Pembayaran
echo =========================================
echo.

echo 1. Clearing all cache...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo    Cache cleared!
echo.

echo 2. Creating storage link...
php artisan storage:link
echo    Storage link created!
echo.

echo 3. Testing syntax...
php -l resources/views/pengurus/verification.blade.php
echo.

echo 4. Starting server...
echo    Server will start at http://127.0.0.1:8000
echo    Test URL: http://127.0.0.1:8000/pengurus/invoice/verification
echo.
echo    Press Ctrl+C to stop
echo.

php artisan serve --host=127.0.0.1 --port=8000
