<?php

namespace App\Filament\Responses;

use App\Services\JwtService;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class FilamentLogoutResponse implements LogoutResponse
{
    public function __construct(
        protected JwtService $jwtService
    ) {}

    public function toResponse($request): RedirectResponse
    {
        $accessToken = $request->cookie('jwt_token')
            ?? (str_starts_with($request->header('Authorization', ''), 'Bearer ')
                ? substr($request->header('Authorization'), 7)
                : null);

        $refreshToken = $request->header('X-Refresh-Token')
            ?? $request->input('refresh_token')
            ?? $request->cookie('refresh_token');

        if ($accessToken) {
            $user = $this->jwtService->getUserFromToken($accessToken);
            if ($user) {
                $this->jwtService->revokeAllUserTokens($user->id);
            }
        }

        if ($refreshToken) {
            $this->jwtService->revokeRefreshToken($refreshToken);
        }

        if (Auth::check()) {
            $this->jwtService->revokeAllUserTokens(Auth::id());
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $response = redirect()->route('login');

        foreach ($this->jwtService->getForgetCookies() as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }
}
