<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $state = Str::random(40);
        if (Auth::check()) {
            Cache::put('oauth_bind_user_' . $state, Auth::id(), now()->addMinutes(15));
        }

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback.
     * Updates the user's primary email address with their verified Google email address.
     */
    public function googleRedirect(Request $request): RedirectResponse
    {
        $state = $request->input('state');
        $bindUserId = $state ? Cache::pull('oauth_bind_user_' . $state) : null;
        /** @var User|null $currentUser */
        $currentUser = Auth::user() ?? ($bindUserId ? User::find($bindUserId) : null);

        if ($request->has('error')) {
            $error = $request->input('error');
            $msg = ($error === 'access_denied')
                ? 'Google Sign-In was cancelled.'
                : 'Google authentication failed: '.$error;

            Log::warning('Google OAuth callback error: '.$msg, $request->all());

            if ($currentUser) {
                return redirect()->route('home')
                    ->with('error', $msg)
                    ->withErrors(['error' => $msg]);
            }

            return redirect()->route('login')
                ->with('error', $msg)
                ->withErrors(['invalid' => $msg]);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed: '.$e->getMessage());
            $failMsg = 'Google authentication failed. Please try again.';
            if ($currentUser) {
                return redirect()->route('home')
                    ->with('error', $failMsg)
                    ->withErrors(['error' => $failMsg]);
            }

            return redirect()->route('login')
                ->with('error', $failMsg)
                ->withErrors(['invalid' => $failMsg]);
        }

        $googleId = $googleUser->getId();
        $googleEmail = $googleUser->getEmail();
        $name = $googleUser->getName() ?? $googleUser->getNickname() ?? 'User';

        if (empty($googleEmail)) {
            $noEmailMsg = 'Google authentication failed. No verified email returned by Google.';
            if ($currentUser) {
                return redirect()->route('home')
                    ->with('error', $noEmailMsg)
                    ->withErrors(['error' => $noEmailMsg]);
            }

            return redirect()->route('login')
                ->with('error', $noEmailMsg)
                ->withErrors(['invalid' => $noEmailMsg]);
        }

        // Case 1: User is already logged in (Linking / Bind flow)
        if ($currentUser) {
            $existing = User::where('google_id', $googleId)->where('id', '!=', $currentUser->id)->first();
            if ($existing) {
                $dupMsg = 'This Google account is already linked to another account.';
                return redirect()->route('home')
                    ->with('error', $dupMsg)
                    ->withErrors(['error' => $dupMsg]);
            }

            // Check if email belongs to another user
            $existingEmail = User::where('email', $googleEmail)->where('id', '!=', $currentUser->id)->first();
            if ($existingEmail) {
                $dupEmailMsg = 'The email associated with this Google account is already registered to another user.';
                return redirect()->route('home')
                    ->with('error', $dupEmailMsg)
                    ->withErrors(['error' => $dupEmailMsg]);
            }

            // Link Google and update primary email
            $currentUser->update([
                'google_id' => $googleId,
                'email' => $googleEmail,
                'email_verified_at' => now(),
            ]);

            $tokens = $this->jwtService->generateTokens($currentUser);
            $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
            $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
            Auth::setUser($currentUser);
            Auth::guard('web')->login($currentUser, true);

            return redirect()->route('home')
                ->with('message', 'Google account connected and primary email updated successfully!')
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
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
