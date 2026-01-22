<?php

// Suppress PHP 8.5+ deprecation warning for PDO::MYSQL_ATTR_SSL_CA in vendor config
// This is a temporary workaround until Laravel updates their config file
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Suppress only the specific PDO::MYSQL_ATTR_SSL_CA deprecation warning from vendor
    if ($errno === E_DEPRECATED && 
        str_contains($errstr, 'PDO::MYSQL_ATTR_SSL_CA') && 
        str_contains($errfile, 'vendor/laravel/framework/config/database.php')) {
        return true; // Suppress this specific warning
    }
    // Let other errors through
    return false;
}, E_DEPRECATED);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON for API routes
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        // Handle authentication exceptions (expired/invalid tokens)
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Authentication failed',
                    'error' => 'Token is invalid or expired. Please log in again.',
                    'code' => 'UNAUTHENTICATED'
                ], 401);
            }
        });

        // Handle validation exceptions with better JSON format
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Catch all other exceptions for API routes and return JSON
        // Laravel will call more specific handlers first, so this only catches unhandled exceptions
        $exceptions->render(function (\Throwable $e, $request) {
            // Only handle API routes and skip exceptions already handled above
            if ($request->is('api/*') 
                && !($e instanceof AuthenticationException) 
                && !($e instanceof ValidationException)) {
                
                // Log the exception for debugging
                \Log::error('API Exception: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'message' => 'Server error occurred',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred. Please try again.',
                    'code' => 'SERVER_ERROR'
                ], 500);
            }
        });
    })->create();
