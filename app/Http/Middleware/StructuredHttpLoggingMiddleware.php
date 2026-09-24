<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StructuredHttpLoggingMiddleware
{
    /**
     * Paths that should be ignored from standard access logging (e.g. k8s probes).
     */
    protected array $except = [
        'up',
        '_octane/*',
        'favicon.ico',
        'build/*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    /**
     * Log structured HTTP access event in clean JSON format.
     */
    protected function logRequest(Request $request, Response $response, float $startTime): void
    {
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return;
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $status = $response->getStatusCode();
        $method = $request->method();
        $path = '/'.ltrim($request->path(), '/');

        // Resolve real client IP behind reverse proxy / Cloudflare
        $clientIp = $request->header('cf-connecting-ip')
            ?? $request->header('x-real-ip')
            ?? $request->header('x-forwarded-for')
            ?? $request->ip();

        if (is_string($clientIp) && str_contains($clientIp, ',')) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }

        $context = [
            'type' => 'http_access',
            'method' => $method,
            'uri' => $request->getRequestUri(),
            'path' => $path,
            'status' => $status,
            'duration_ms' => $durationMs,
            'client_ip' => $clientIp,
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
        ];

        // Format concise summary message
        $message = "HTTP {$method} {$path} {$status} ({$durationMs}ms)";
        error_log("🌐 {$message}");

        if ($status >= 500) {
            Log::error($message, $context);
        } elseif ($status >= 400) {
            Log::warning($message, $context);
        } else {
            Log::info($message, $context);
        }
    }
}
