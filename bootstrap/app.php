<?php

use App\Http\Middleware\RequireActiveAccount;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UserAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->expectsJson() || $exception instanceof AuthenticationException
                || $exception instanceof ValidationException || $exception instanceof HttpResponseException) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
            if ($status < 400 || $status > 599) {
                return null;
            }
            $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

            return response()->view('errors.page', ['statusCode' => $status], $status, $headers)
                ->header('Cache-Control', 'no-store, private')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('X-Frame-Options', 'DENY')
                ->header('Referrer-Policy', 'same-origin')
                ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
        });
    })->create()
    // All configuration is provided in config/ to avoid loading the framework's
    // legacy MySQL PDO constants on PHP 8.5.
    ->dontMergeFrameworkConfiguration();
