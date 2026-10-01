<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Filament\Http\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Auth;

class FilamentAuthenticate extends BaseAuthenticate
{
    public function __construct(
        AuthFactory $auth,
        protected JwtService $jwtService
    ) {
        parent::__construct($auth);
    }

    /**
     * Authenticate user via JWT tokens (cookies or headers) before Filament guard check.
     */
    protected function authenticate($request, array $guards): void
    {
        $accessToken = $this->extractAccessToken($request);
        $user = null;

        if ($accessToken) {
            $user = $this->jwtService->getUserFromToken($accessToken);
        }

        // If access token is expired, attempt auto-refresh using refresh token
        if (! $user) {
            $refreshToken = $this->extractRefreshToken($request);
            if ($refreshToken) {
                $rotationResult = $this->jwtService->rotateRefreshToken($refreshToken);
                if ($rotationResult) {
                    $user = $rotationResult['user'];
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

        parent::authenticate($request, $guards);
    }

    protected function extractAccessToken($request): ?string
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return $request->cookie('jwt_token');
    }

    protected function extractRefreshToken($request): ?string
    {
        return $request->header('X-Refresh-Token')
            ?? $request->input('refresh_token')
            ?? $request->cookie('refresh_token');
    }

    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
