<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(protected JwtService $jwtService) {}

    /**
     * Redirect to Google OAuth.
     */
    public function google(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback.
     * Updates the user's primary email address with their verified Google email address.
     */
    public function googleRedirect(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed: '.$e->getMessage());
            return redirect()->route('login')->withErrors([
                'invalid' => 'Google authentication failed. Please try again.',
            ]);
        }

        $googleId = $googleUser->getId();
        $googleEmail = $googleUser->getEmail();
        $name = $googleUser->getName() ?? $googleUser->getNickname() ?? 'User';

        if (empty($googleEmail)) {
            return redirect()->route('login')->withErrors([
                'invalid' => 'Google authentication failed. No verified email returned by Google.',
            ]);
        }

        // Case 1: User is already logged in (Linking flow)
        if (Auth::check()) {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            $existing = User::where('google_id', $googleId)->where('id', '!=', $currentUser->id)->first();
            if ($existing) {
                return redirect()->route('home')->withErrors([
                    'error' => 'This Google account is already linked to another account.',
                ]);
            }

            // Check if email belongs to another user
            $existingEmail = User::where('email', $googleEmail)->where('id', '!=', $currentUser->id)->first();
            if ($existingEmail) {
                return redirect()->route('home')->withErrors([
                    'error' => 'The email associated with this Google account is already registered to another user.',
                ]);
            }

            // Link Google and update primary email
            $currentUser->update([
                'google_id' => $googleId,
                'email' => $googleEmail,
                'email_verified_at' => now(),
            ]);

            return redirect()->route('home')->with('message', 'Google account connected and primary email updated successfully!');
        }

        // Case 2: Find existing user by google_id
        $user = User::where('google_id', $googleId)->first();

        // Case 3: If not found by google_id, match by email
        if (! $user) {
            $user = User::where('email', $googleEmail)->first();
            if ($user) {
                $user->update([
                    'google_id' => $googleId,
                    'email_verified_at' => now(),
                ]);
            }
        }

        // Case 4: New user registration via Google -> Redirect to Set Password flow
        if (! $user) {
            $nameParts = array_values(array_filter(explode(' ', trim($name))));
            $firstInitial = $nameParts[0][0] ?? 'U';
            $secondInitial = $nameParts[1][0] ?? '';
            $avatar = strtoupper($firstInitial.$secondInitial);

            session([
                'oauth_pending_user' => [
                    'name' => $name,
                    'email' => $googleEmail,
                    'avatar' => $avatar,
                    'provider' => 'google',
                    'provider_id' => $googleId,
                ],
            ]);

            return redirect()->route('auth.set-password');
        }

        // Ensure primary email is kept up-to-date with verified Google email
        if ($user->email !== $googleEmail) {
            $user->update([
                'email' => $googleEmail,
                'email_verified_at' => now(),
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

    /**
     * Prevent Google unbinding.
     * Rule: Google account cannot be unbound ("kalo google itu ga bisa unbind").
     */
    public function unbind(Request $request): JsonResponse|RedirectResponse
    {
        $message = 'Google account is the primary verified identity and cannot be unbound.';

        if ($request->wantsJson()) {
            return response()->json([
                'error' => 'forbidden',
                'message' => $message,
            ], 403);
        }

        return redirect()->back()->withErrors([
            'error' => $message,
        ]);
    }
}
