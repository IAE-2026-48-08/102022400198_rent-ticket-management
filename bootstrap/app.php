<?php

use App\Http\Middleware\ValidateApiKey;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mendaftarkan alias middleware API Key Anda agar tetap aktif
        $middleware->alias([
            'api.key' => ValidateApiKey::class,
            'auth.apikey' => ValidateApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $previous = $e->getPrevious();

                $message = $previous instanceof ModelNotFoundException
                    ? class_basename($previous->getModel()).' tidak ditemukan'
                    : 'Resource atau Endpoint tidak ditemukan';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => $e->getMessage(),
                    'meta' => [
                        'service_name' => 'Service Manajemen Tiket Tenant',
                        'api_version' => 'v1',
                    ],
                ], 404);
            }
        });
    })->create();