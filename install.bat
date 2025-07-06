@echo off
echo ========================================
echo   EnviroPay Installation Script
echo ========================================
echo.

echo [1/8] Checking PHP version...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP not found. Please install PHP 8.2+ first.
    pause
    exit /b 1
)

echo [2/8] Installing Composer dependencies...
call composer install
if %errorlevel% neq 0 (
    echo ERROR: Failed to install Composer dependencies.
    pause
    exit /b 1
)

echo [3/8] Installing NPM dependencies...
call npm install
if %errorlevel% neq 0 (
    echo ERROR: Failed to install NPM dependencies.
    pause
    exit /b 1
)

echo [4/8] Setting up environment file...
if not exist .env (
    copy .env.example .env
    echo Environment file created successfully.
) else (
    echo Environment file already exists.
)

echo [5/8] Generating application key...
call php artisan key:generate
if %errorlevel% neq 0 (
    echo ERROR: Failed to generate application key.
    pause
    exit /b 1
)

echo [6/8] Running database migrations...
call php artisan migrate
if %errorlevel% neq 0 (
    echo WARNING: Database migration failed. Please check your database configuration in .env file.
)

echo [7/8] Creating storage link...
call php artisan storage:link
if %errorlevel% neq 0 (
    echo WARNING: Failed to create storage link.
)

echo [8/8] Building frontend assets...
call npm run build
if %errorlevel% neq 0 (
    echo WARNING: Failed to build frontend assets.
)

echo.
echo ========================================
echo   Installation Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Configure your database in .env file
echo 2. Run: php artisan migrate
echo 3. Run: php artisan db:seed (optional)
echo 4. Start server: php artisan serve
echo.
echo Your application will be available at: http://localhost:8000
echo.
pause
