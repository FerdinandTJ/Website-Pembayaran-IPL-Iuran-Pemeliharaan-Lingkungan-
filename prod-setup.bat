@echo off
echo ========================================
echo   EnviroPay Production Setup
echo ========================================
echo.

echo [1/6] Installing production dependencies...
call composer install --optimize-autoloader --no-dev

echo [2/6] Building production assets...
call npm run build

echo [3/6] Optimizing application...
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache
call php artisan event:cache

echo [4/6] Running database migrations...
call php artisan migrate --force

echo [5/6] Setting up storage...
call php artisan storage:link

echo [6/6] Final optimization...
call php artisan optimize

echo.
echo ========================================
echo   Production Setup Complete!
echo ========================================
echo.
echo Important production checklist:
echo [x] APP_ENV=production in .env
echo [x] APP_DEBUG=false in .env  
echo [x] Set strong APP_KEY
echo [x] Configure proper database credentials
echo [x] Set up SSL certificate
echo [x] Configure backup system
echo [x] Set up monitoring
echo.
echo Your application is ready for production!
echo.
pause
