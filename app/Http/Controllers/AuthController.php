<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use App\Services\ResendMailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        protected JwtService $jwtService,
        protected ResendMailService $resendMailService
    ) {}

    public function login(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return Inertia::render('Login');
    }

    public function loginPost(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return redirect()->back()->withErrors([
                'invalid' => 'Invalid email or password',
            ])->withInput();
        }

        $tokens = $this->jwtService->generateTokens($user);
        $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
        Auth::setUser($user);
        Auth::guard('web')->login($user, true);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Login successful',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ])
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        }

        return redirect()->route('home')
            ->with('message', 'Login successful!')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    public function register(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return Inertia::render('Register');
    }

    public function registerPost(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|same:password_confirm',
            'password_confirm' => 'required|min:8',
        ]);

        $nameParts = array_values(array_filter(explode(' ', trim($validated['name']))));
        $firstInitial = $nameParts[0][0] ?? 'U';
        $secondInitial = $nameParts[1][0] ?? '';
        $avatar = strtoupper($firstInitial.$secondInitial);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'avatar' => $avatar,
                'password' => Hash::make($validated['password']),
            ]);

            $tokens = $this->jwtService->generateTokens($user);
            $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
            $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
            Auth::setUser($user);
            Auth::guard('web')->login($user, true);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Registration successful',
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                    ],
                ], 201)
                    ->withCookie($accessCookie)
                    ->withCookie($refreshCookie);
            }

            return redirect()->route('home')
                ->with('message', 'Account registered successfully!')
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors([
                'error' => 'An error occurred during registration. Please try again.',
            ])->withInput();
        }
    }

    /**
     * Handle refresh token request to renew access token (with threshold rotation).
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $refreshToken = $request->header('X-Refresh-Token')
            ?? $request->input('refresh_token')
            ?? $request->cookie('refresh_token');

        if (! $refreshToken) {
            return response()->json([
                'message' => 'Refresh token missing. Please login again.',
            ], 401);
        }

        $rotationResult = $this->jwtService->rotateRefreshToken($refreshToken);

        if (! $rotationResult) {
            return response()->json([
                'message' => 'Invalid, expired, or revoked refresh token. Please login again.',
            ], 401);
        }

        $user = $rotationResult['user'];
        $accessCookie = $this->jwtService->getAccessTokenCookie($rotationResult['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($rotationResult['refresh_token']);
        Auth::setUser($user);

        return response()->json([
            'message' => 'Token refreshed successfully',
            'access_token' => $rotationResult['access_token'],
            'refresh_token' => $rotationResult['refresh_token'],
            'rotated' => $rotationResult['rotated'],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
        ])
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $refreshToken = $request->header('X-Refresh-Token')
            ?? $request->input('refresh_token')
            ?? $request->cookie('refresh_token');

        if ($refreshToken) {
            $this->jwtService->revokeRefreshToken($refreshToken);
        }

        if (Auth::check()) {
            $this->jwtService->revokeAllUserTokens(Auth::id());
        }

        Auth::logout();
        $forgetCookies = $this->jwtService->getForgetCookies();

        if ($request->session()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson()) {
            $response = response()->json([
                'message' => 'Logged out successfully',
            ]);
            foreach ($forgetCookies as $cookie) {
                $response->withCookie($cookie);
            }

            return $response;
        }

        $response = redirect()->route('home');
        foreach ($forgetCookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    /**
     * Show Forgot Password request form.
     */
    public function forgotPassword(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return Inertia::render('ForgotPassword');
    }

    /**
     * Send password reset link email via Resend.
     */
    public function sendResetLinkEmail(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );

            // Dispatch email via Resend service
            $this->resendMailService->sendPasswordResetEmail($user, $plainToken);
        }

        $successMessage = 'If that email address exists in our database, we have sent a password reset link.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $successMessage,
            ]);
        }

        return redirect()->back()->with('message', $successMessage);
    }

    /**
     * Show Reset Password form.
     */
    public function resetPasswordPage(Request $request, string $token): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return Inertia::render('ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Handle Password Reset submission.
     */
    public function resetPassword(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|same:password_confirm',
            'password_confirm' => 'required|min:8',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            return redirect()->back()->withErrors([
                'invalid' => 'This password reset link is invalid or has expired. Please request a new one.',
            ])->withInput();
        }

        // Check if token expired (valid for 60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return redirect()->back()->withErrors([
                'invalid' => 'This password reset link has expired. Please request a new one.',
            ])->withInput();
        }

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return redirect()->back()->withErrors([
                'invalid' => 'User account not found.',
            ])->withInput();
        }

        // Update password and clean up reset token
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        // Revoke old tokens & log user in
        $this->jwtService->revokeAllUserTokens($user->id);
        $tokens = $this->jwtService->generateTokens($user);
        $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
        Auth::setUser($user);
        Auth::guard('web')->login($user, true);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Your password has been reset successfully!',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ])
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        }

        return redirect()->route('home')
            ->with('message', 'Your password has been reset successfully!')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    /**
     * Show Set Password form for OAuth sign-ins.
     */
    public function showSetPassword(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $pending = session('oauth_pending_user');
        if (! $pending) {
            return redirect()->route('login')->withErrors([
                'invalid' => 'No pending authentication found. Please sign in again.',
            ]);
        }

        return Inertia::render('SetPassword', [
            'oauthUser' => $pending,
        ]);
    }

    /**
     * Handle Password submission for OAuth registrations.
     */
    public function storeSetPassword(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'password' => 'required|min:8|same:password_confirm',
            'password_confirm' => 'required|min:8',
        ]);

        $pending = session('oauth_pending_user');
        if (! $pending) {
            return redirect()->route('login')->withErrors([
                'invalid' => 'Session expired. Please sign in with Google or Apple again.',
            ]);
        }

        $email = $pending['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            $updateData = [
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ];
            if ($pending['provider'] === 'google') {
                $updateData['google_id'] = $pending['provider_id'];
            } elseif ($pending['provider'] === 'apple') {
                $updateData['apple_id'] = $pending['provider_id'];
            }
            $user->update($updateData);
        } else {
            $user = User::create([
                'name' => $pending['name'],
                'email' => $email,
                'avatar' => $pending['avatar'],
                'google_id' => $pending['provider'] === 'google' ? $pending['provider_id'] : null,
                'apple_id' => $pending['provider'] === 'apple' ? $pending['provider_id'] : null,
                'auth_provider' => $pending['provider'],
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
            ]);
        }

        session()->forget('oauth_pending_user');

        $tokens = $this->jwtService->generateTokens($user);
        $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
        Auth::setUser($user);
        Auth::guard('web')->login($user, true);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Password created and account setup successfully!',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ], 201)
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        }

        return redirect()->route('home')
            ->with('message', 'Password created and account setup successfully!')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    /**
     * Cancel OAuth set-password flow.
     */
    public function cancelSetPassword(): RedirectResponse
    {
        session()->forget('oauth_pending_user');
        return redirect()->route('login');
    }
}
