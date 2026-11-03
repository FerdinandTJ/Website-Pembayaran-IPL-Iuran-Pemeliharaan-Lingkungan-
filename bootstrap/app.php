<?php

use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UserAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [SecurityHeaders::class, RequireActiveAccount::class]);
        $middleware->alias([
            'role' => UserAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create()
    // All configuration is provided in config/ to avoid loading the framework's
    // legacy MySQL PDO constants on PHP 8.5.
    ->dontMergeFrameworkConfiguration();
