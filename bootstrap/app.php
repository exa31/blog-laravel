<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\StructuredHttpLoggingMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: [
            'jwt_token',
            'refresh_token',
        ]);

        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        $middleware->web(append: [
            StructuredHttpLoggingMiddleware::class,
            JwtAuthMiddleware::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'jwt.auth' => JwtAuthMiddleware::class.':required',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->header('X-Inertia')) {
                return back();
            }
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Token mismatch, retrying.'], 200);
            }

            return redirect()->back();
        });

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() === 419) {
                if ($request->header('X-Inertia')) {
                    return back();
                }
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => 'Session refreshed, please retry.'], 200);
                }

                return redirect()->back();
            }

            return $response;
        });
    })->create();
