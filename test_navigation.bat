@echo off
echo ==========================================
echo Testing Navigation Pengurus Pages
echo ==========================================
echo.

echo 1. Clearing all cache...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo    All cache cleared!
echo.

echo 2. Checking syntax of all pengurus views...
echo    - member.blade.php:
php -l resources/views/pengurus/member.blade.php
echo    - invoice.blade.php:
php -l resources/views/pengurus/invoice.blade.php
echo    - cashflow.blade.php:
php -l resources/views/pengurus/cashflow.blade.php
echo    - verification.blade.php:
php -l resources/views/pengurus/verification.blade.php
echo.

echo 3. Starting server...
echo    Test URLs with Navigation:
echo    - Dashboard: http://127.0.0.1:8000/pengurus/dashboard
echo    - Members:   http://127.0.0.1:8000/pengurus/members
echo    - Invoice:   http://127.0.0.1:8000/pengurus/invoice
echo    - Cashflow:  http://127.0.0.1:8000/pengurus/cashflow
echo    - Verify:    http://127.0.0.1:8000/pengurus/invoice/verification
echo.
echo    Press Ctrl+C to stop server
echo.

php artisan serve --host=127.0.0.1 --port=8000
