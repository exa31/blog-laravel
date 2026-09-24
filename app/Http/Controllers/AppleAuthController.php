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
     */
    public function apple(): RedirectResponse
    {
        // Set dynamic client_secret if needed
        $clientSecret = $this->appleTokenService->getClientSecret();
        if ($clientSecret) {
            config(['services.apple.client_secret' => $clientSecret]);
        }

        try {
            return Socialite::driver('apple')
                ->scopes(['name', 'email'])
                ->stateless()
                ->redirect();
        } catch (\Throwable $e) {
            Log::error('Apple OAuth redirect failed: '.$e->getMessage());

            // Build manual Apple OAuth authorize URL as reliable fallback
            $clientId = config('services.apple.client_id');
            $redirectUri = urlencode(config('services.apple.redirect', url('/auth/apple-callback')));
            $state = Str::random(40);
            session(['apple_oauth_state' => $state]);

            $appleAuthUrl = "https://appleid.apple.com/auth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code%20id_token&scope=name%20email&response_mode=form_post&state={$state}";

            return redirect()->away($appleAuthUrl);
        }
    }

    /**
     * Handle Apple callback (supports both form_post POST and GET redirects).
     */
    public function appleRedirect(Request $request): RedirectResponse
    {
        $idToken = $request->input('id_token');
        $code = $request->input('code');
        $userJson = $request->input('user');

        $appleId = null;
        $email = null;
        $name = null;

        // Extract data from id_token JWT
        if (! empty($idToken)) {
            $tokenPayload = $this->appleTokenService->decodeToken($idToken);
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
            return redirect()->route('login')->withErrors([
                'invalid' => 'Apple authentication failed. No valid Apple credentials received.',
            ]);
        }

        // Case 1: User is already logged in (Linking flow)
        if (Auth::check()) {
            /** @var User $currentUser */
            $currentUser = Auth::user();

            // Check if this Apple ID is already linked to a different account
            $existing = User::where('apple_id', $appleId)->where('id', '!=', $currentUser->id)->first();
            if ($existing) {
                return redirect()->route('home')->withErrors([
                    'error' => 'This Apple ID is already linked to another account.',
                ]);
            }

            $currentUser->update([
                'apple_id' => $appleId,
            ]);

            return redirect()->route('home')->with('message', 'Apple ID connected successfully!');
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

        // Case 4: Create new user registered via Apple ID
        if (! $user) {
            $resolvedEmail = $email ?? ($appleId.'@privaterelay.appleid.com');
            $resolvedName = ! empty($name) ? $name : 'Apple User';

            $nameParts = array_values(array_filter(explode(' ', trim($resolvedName))));
            $firstInitial = $nameParts[0][0] ?? 'A';
            $secondInitial = $nameParts[1][0] ?? '';
            $avatar = strtoupper($firstInitial.$secondInitial);

            $user = User::create([
                'name' => $resolvedName,
                'email' => $resolvedEmail,
                'avatar' => $avatar,
                'apple_id' => $appleId,
                'auth_provider' => 'apple',
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ]);
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

        // Enforcement: Cannot unbind Apple ID unless Google account is linked!
        if (empty($user->google_id)) {
            $msg = 'Cannot disconnect Apple ID. You must sign in with Google first to verify and update your primary email before unbinding Apple.';
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
