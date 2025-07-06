@echo off
echo ========================================
echo   EnviroPay Development Setup
echo ========================================
echo.

echo [1/5] Clearing all caches...
call php artisan cache:clear
call php artisan config:clear
call php artisan view:clear
call php artisan route:clear

echo [2/5] Optimizing for development...
call php artisan config:cache

echo [3/5] Running database migrations with fresh data...
call php artisan migrate:fresh --seed

echo [4/5] Creating storage directories...
if not exist "storage\app\public\proofs" mkdir "storage\app\public\proofs"
call php artisan storage:link

echo [5/5] Starting development server...
echo.
echo ========================================
echo   Development server starting...
echo ========================================
echo.
echo Access your application at: http://localhost:8000
echo.
echo Default login credentials:
echo Admin    - admin@enviropay.com / admin123
echo Pengurus - pengurus@enviropay.com / pengurus123  
echo Warga    - warga@enviropay.com / warga123
echo.

call php artisan serve
