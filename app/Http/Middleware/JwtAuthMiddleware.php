<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(protected JwtService $jwtService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        // Allow dedicated refresh endpoint to handle rotation directly
        if ($request->is('refresh')) {
            return $next($request);
        }

        $newAccessTokenCookie = null;
        $newRefreshTokenCookie = null;
        $accessToken = $this->extractAccessToken($request);
        $user = null;

        if ($accessToken) {
            $user = $this->jwtService->getUserFromToken($accessToken);
        }

        // If access token is expired/missing, attempt auto-refresh using Refresh Token
        if (! $user) {
            $refreshToken = $this->extractRefreshToken($request);
            if ($refreshToken) {
                $rotationResult = $this->jwtService->rotateRefreshToken($refreshToken);
                if ($rotationResult) {
                    $user = $rotationResult['user'];
                    $newAccessTokenCookie = $this->jwtService->getAccessTokenCookie($rotationResult['access_token']);

                    if ($rotationResult['rotated']) {
                        $newRefreshTokenCookie = $this->jwtService->getRefreshTokenCookie($rotationResult['refresh_token']);
                    }
                }
            }
        }

        if ($user) {
            Auth::setUser($user);
            Auth::guard('web')->setUser($user);
            if ($request->hasSession()) {
                Auth::guard('web')->login($user);
            }
        }

        // If route requires authentication and user is not resolved
        if ($guard === 'required' && ! Auth::check()) {
            if ($request->expectsJson() || $request->is('like/*') || $request->is('comments*') || $request->is('save-post*')) {
                return response()->json([
                    'message' => 'Unauthenticated. Please log in to continue.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        $response = $next($request);

        // Attach newly refreshed access token cookie if regenerated
        if ($newAccessTokenCookie && method_exists($response, 'withCookie')) {
            $response->withCookie($newAccessTokenCookie);
        }

        // Attach rotated refresh token cookie if rotated
        if ($newRefreshTokenCookie && method_exists($response, 'withCookie')) {
            $response->withCookie($newRefreshTokenCookie);
        }

        return $response;
    }

    /**
     * Extract access token from Bearer header or cookie.
     */
    protected function extractAccessToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return $request->cookie('jwt_token');
    }

    /**
     * Extract refresh token from header, body, or cookie.
     */
    protected function extractRefreshToken(Request $request): ?string
    {
        return $request->header('X-Refresh-Token')
            ?? $request->input('refresh_token')
            ?? $request->cookie('refresh_token');
    }
}
