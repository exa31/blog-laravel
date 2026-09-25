<?php

namespace App\Http\Controllers;

use App\Events\AppleIdRevoked;
use App\Models\User;
use App\Services\AppleTokenService;
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

class AppleAuthController extends Controller
{
    public function __construct(
        protected JwtService $jwtService,
        protected AppleTokenService $appleTokenService
    ) {}

    /**
     * Redirect user to Apple Sign-In.
     * Uses OpenID Connect form_post response mode to receive id_token directly (no .p8 private key required).
     */
    public function apple(): RedirectResponse
    {
        $clientId = config('services.apple.client_id', env('APPLE_CLIENT_ID'));
        $redirectUri = urlencode(config('services.apple.redirect', url('/auth/apple-callback')));
        $state = Str::random(40);
        $nonce = Str::random(40);

        if (Auth::check()) {
            Cache::put('oauth_bind_user_' . $state, Auth::id(), now()->addMinutes(15));
        }

        session(['apple_oauth_state' => $state]);

        $appleAuthUrl = "https://appleid.apple.com/auth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code%20id_token&scope=name%20email&response_mode=form_post&state={$state}&nonce={$nonce}";

        return redirect()->away($appleAuthUrl);
    }

    /**
     * Handle Apple callback (supports both form_post POST and GET redirects).
     */
    public function appleRedirect(Request $request): RedirectResponse
    {
        $state = $request->input('state');
        $bindUserId = $state ? Cache::pull('oauth_bind_user_' . $state) : null;
        /** @var User|null $currentUser */
        $currentUser = Auth::user() ?? ($bindUserId ? User::find($bindUserId) : null);

        // Check if Apple returned an error callback (e.g. user cancelled authorization)
        if ($request->has('error')) {
            $error = $request->input('error');
            $errorDesc = $request->input('error_description');
            $msg = ($error === 'user_cancelled_authorize')
                ? 'Apple Sign-In was cancelled.'
                : 'Apple authentication failed: ' . ($errorDesc ?: $error);

            Log::warning('Apple OAuth callback error: ' . $msg, $request->all());

            if ($currentUser) {
                return redirect()->route('home')
                    ->with('error', $msg)
                    ->withErrors(['error' => $msg]);
            }

            return redirect()->route('login')
                ->with('error', $msg)
                ->withErrors(['invalid' => $msg]);
        }

        $idToken = $request->input('id_token');
        $code = $request->input('code');
        $userJson = $request->input('user');

        $appleId = null;
        $email = null;
        $name = null;

        // 1. Extract and verify data from id_token JWT (Public Key verification)
        if (! empty($idToken)) {
            $tokenPayload = $this->appleTokenService->verifyIdToken($idToken);
            if ($tokenPayload) {
                $appleId = $tokenPayload['sub'] ?? null;
                $email = $tokenPayload['email'] ?? null;
            }
        }

        // Try Socialite driver if available
        if (! $appleId) {
            try {
                $clientSecret = $this->appleTokenService->getClientSecret();
                if ($clientSecret) {
                    config(['services.apple.client_secret' => $clientSecret]);
                }

                $socialiteUser = Socialite::driver('apple')->stateless()->user();
                $appleId = $socialiteUser->getId();
                $email = $socialiteUser->getEmail() ?? $email;
                $name = $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? $name;
            } catch (\Throwable $e) {
                Log::warning('Socialite Apple callback parsing fallback: '.$e->getMessage());
            }
        }

        // Parse user profile JSON sent by Apple on first sign-in
        if (! empty($userJson)) {
            $parsedUser = is_array($userJson) ? $userJson : json_decode($userJson, true);
            if (is_array($parsedUser)) {
                $firstName = $parsedUser['name']['firstName'] ?? '';
                $lastName = $parsedUser['name']['lastName'] ?? '';
                $fullName = trim("{$firstName} {$lastName}");
                if (! empty($fullName)) {
                    $name = $fullName;
                }
                if (empty($email) && ! empty($parsedUser['email'])) {
                    $email = $parsedUser['email'];
                }
            }
        }

        if (empty($appleId)) {
            $failMsg = 'Apple authentication failed. No valid Apple credentials received.';
            if ($currentUser) {
                return redirect()->route('home')
                    ->with('error', $failMsg)
                    ->withErrors(['error' => $failMsg]);
            }

            return redirect()->route('login')
                ->with('error', $failMsg)
                ->withErrors(['invalid' => $failMsg]);
        }

        // Case 1: User is already logged in (Linking / Bind flow)
        if ($currentUser) {
            // Check if this Apple ID is already linked to a different account
            $existing = User::where('apple_id', $appleId)->where('id', '!=', $currentUser->id)->first();
            if ($existing) {
                $errMsg = 'This Apple ID is already linked to another account.';
                return redirect()->route('home')
                    ->with('error', $errMsg)
                    ->withErrors(['error' => $errMsg]);
            }

            // Check if email from Apple is already registered to a different user
            if (! empty($email)) {
                $existingEmail = User::where('email', $email)->where('id', '!=', $currentUser->id)->first();
                if ($existingEmail) {
                    $errMsg = "The email ({$email}) associated with this Apple ID is already registered to another account.";
                    return redirect()->route('home')
                        ->with('error', $errMsg)
                        ->withErrors(['error' => $errMsg]);
                }
            }

            $currentUser->update([
                'apple_id' => $appleId,
            ]);

            $tokens = $this->jwtService->generateTokens($currentUser);
            $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
            $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
            Auth::setUser($currentUser);
            Auth::guard('web')->login($currentUser, true);

            return redirect()->route('home')
                ->with('message', 'Apple ID connected successfully!')
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        }

        // Case 2: Find existing user by apple_id
        $user = User::where('apple_id', $appleId)->first();

        // Case 3: If not found by apple_id, check by email
        if (! $user && ! empty($email)) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update([
                    'apple_id' => $appleId,
                ]);
            }
        }

        // Case 4: New user registration via Apple ID -> Redirect to Set Password flow
        if (! $user) {
            $resolvedEmail = $email ?? ($appleId.'@privaterelay.appleid.com');
            $resolvedName = ! empty($name) ? $name : 'Apple User';

            $nameParts = array_values(array_filter(explode(' ', trim($resolvedName))));
            $firstInitial = $nameParts[0][0] ?? 'A';
            $secondInitial = $nameParts[1][0] ?? '';
            $avatar = strtoupper($firstInitial.$secondInitial);

            session([
                'oauth_pending_user' => [
                    'name' => $resolvedName,
                    'email' => $resolvedEmail,
                    'avatar' => $avatar,
                    'provider' => 'apple',
                    'provider_id' => $appleId,
                ],
            ]);

            return redirect()->route('auth.set-password');
        }

        // Issue JWT tokens and cookies
        $tokens = $this->jwtService->generateTokens($user);
        $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
        Auth::setUser($user);
        Auth::guard('web')->login($user, true);

        return redirect()->route('home')
            ->with('message', 'Logged in with Apple successfully!')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    /**
     * Apple Server-to-Server Notification Webhook (revocation, consent-revoked, account deletion).
     */
    public function webhook(Request $request): JsonResponse
    {
        $signedPayload = $request->input('signed_payload') ?? $request->getContent();

        if (empty($signedPayload)) {
            return response()->json(['error' => 'Missing signed payload'], 400);
        }

        $notification = $this->appleTokenService->parseServerNotification($signedPayload);

        if (! $notification || empty($notification['sub'])) {
            return response()->json(['error' => 'Invalid or unparseable notification payload'], 400);
        }

        $appleId = $notification['sub'];
        $eventType = $notification['event_type'];
        $user = User::where('apple_id', $appleId)->first();

        Log::info("Apple Webhook received: [{$eventType}] for Apple ID: {$appleId}");

        // Dispatch revocation event
        event(new AppleIdRevoked(
            appleId: $appleId,
            eventType: $eventType,
            payload: $notification['payload'] ?? [],
            user: $user
        ));

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Unbind / Disconnect Apple ID.
     * Rule: If user has Apple ID, they can ONLY unbind if Google account is connected.
     */
    public function unbind(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || empty($user->apple_id)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'No Apple ID linked to this account.'], 422);
            }
            return redirect()->back()->withErrors(['error' => 'No Apple ID linked to this account.']);
        }

        // Enforcement: Cannot unbind Apple ID unless user has an alternative verified identity
        if (! $user->can_unbind_apple) {
            $msg = 'Cannot disconnect Apple ID. Your account does not have an alternative verified email or connected Google account. Please link Google or verify your primary email first.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 403);
            }
            return redirect()->back()->withErrors(['error' => $msg]);
        }

        $user->update([
            'apple_id' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Apple ID disconnected successfully.',
                'user' => $user->fresh(),
            ]);
        }

        return redirect()->back()->with('message', 'Apple ID disconnected successfully.');
    }
}
