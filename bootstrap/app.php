<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserHasRoutePermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'permission.route' => EnsureUserHasRoutePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Unauthenticated.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->expectsJson() || $exception instanceof AuthenticationException) {
                return null;
            }

            if (! $exception instanceof HttpExceptionInterface) {
                return null;
            }

            $statusCode = $exception->getStatusCode();

            if ($statusCode < 500) {
                return null;
            }

            return response()->view('errors.friendly', [
                'statusCode' => $statusCode,
                'exception' => $exception,
            ], $statusCode);
        });
    })->create();
