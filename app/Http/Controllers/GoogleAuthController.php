<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(protected JwtService $jwtService) {}

    public function google(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function googleRedirect(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'invalid' => 'Google authentication failed. Please try again.',
            ]);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $name = $googleUser->getName() ?? $googleUser->getNickname() ?? 'User';
            $nameParts = array_values(array_filter(explode(' ', trim($name))));
            $firstInitial = $nameParts[0][0] ?? 'U';
            $secondInitial = $nameParts[1][0] ?? '';
            $avatar = strtoupper($firstInitial.$secondInitial);

            $user = User::create([
                'name' => $name,
                'email' => $googleUser->getEmail(),
                'avatar' => $avatar,
                'password' => Hash::make(Str::random(24)),
            ]);
        }

        $tokens = $this->jwtService->generateTokens($user);
        $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
        Auth::setUser($user);
        Auth::guard('web')->login($user, true);

        return redirect()->route('home')
            ->with('message', 'Logged in with Google successfully!')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }
}
