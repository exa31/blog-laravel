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
use Illuminate\Support\Facades\RateLimiter;
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

        // If user registered with email and has not verified email yet, prompt verification
        if ($user->auth_provider === 'email' && empty($user->email_verified_at)) {
            $lastRecord = DB::table('email_verification_codes')->where('email', $user->email)->first();
            $dailyKey = 'verify-resend-daily:'.strtolower($user->email);

            $isCooldown = $lastRecord && Carbon::parse($lastRecord->created_at)->addSeconds(60)->isFuture();
            $isOverDailyLimit = RateLimiter::tooManyAttempts($dailyKey, 3);

            if (! $isCooldown && ! $isOverDailyLimit) {
                $code = sprintf('%06d', random_int(100000, 999999));
                DB::table('email_verification_codes')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'code' => Hash::make($code),
                        'created_at' => now(),
                    ]
                );
                RateLimiter::hit($dailyKey, 86400);
                $this->resendMailService->sendVerificationCodeEmail($user, $code);
            }

            session(['verify_email_address' => $user->email]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Please verify your email before logging in. We have sent a verification code to your email.',
                    'requires_verification' => true,
                    'email' => $user->email,
                ], 403);
            }

            return redirect()->route('verification.notice')
                ->with('message', 'Please verify your email before logging in. We have sent a verification code to your email.');
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
                'auth_provider' => 'email',
                'email_verified_at' => null,
                'password' => Hash::make($validated['password']),
            ]);

            $code = sprintf('%06d', random_int(100000, 999999));
            DB::table('email_verification_codes')->updateOrInsert(
                ['email' => $user->email],
                [
                    'code' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            $this->resendMailService->sendVerificationCodeEmail($user, $code);
            RateLimiter::hit('verify-resend-daily:'.strtolower($user->email), 86400);
            session(['verify_email_address' => $user->email]);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Registration initiated. Please verify your email with the 6-digit code.',
                    'requires_verification' => true,
                    'email' => $user->email,
                ], 201);
            }

            return redirect()->route('verification.notice')
                ->with('message', 'Registration successful! Please enter the 6-digit code sent to your email.');
        } catch (\Throwable $e) {
            Log::error('Registration exception: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->withErrors([
                'error' => 'An error occurred during registration. Please try again.',
            ])->withInput();
        }
    }

    /**
     * Show Email Verification Code page.
     */
    public function verifyEmailPage(Request $request): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()->email_verified_at) {
            return redirect()->route('home');
        }

        $email = session('verify_email_address') ?? (Auth::check() ? Auth::user()->email : null);
        if (empty($email)) {
            return redirect()->route('register');
        }

        return Inertia::render('VerifyEmail', [
            'email' => $email,
        ]);
    }

    /**
     * Verify 6-digit email verification code.
     */
    public function verifyEmailCode(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return redirect()->back()->withErrors([
                'invalid' => 'User account not found.',
            ])->withInput();
        }

        if (! empty($user->email_verified_at)) {
            $tokens = $this->jwtService->generateTokens($user);
            $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
            $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
            Auth::setUser($user);
            Auth::guard('web')->login($user, true);

            return redirect()->route('home')
                ->with('message', 'Email is already verified. Logged in successfully!')
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        }

        $record = DB::table('email_verification_codes')->where('email', $validated['email'])->first();

        if (! $record || ! Hash::check($validated['code'], $record->code)) {
            return redirect()->back()->withErrors([
                'invalid' => 'Invalid verification code. Please check your email and try again.',
            ])->withInput();
        }

        // Check if code has expired (15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('email_verification_codes')->where('email', $validated['email'])->delete();

            return redirect()->back()->withErrors([
                'invalid' => 'Verification code has expired. Please request a new code.',
            ])->withInput();
        }

        // Mark user verified and remove code
        $user->update([
            'email_verified_at' => now(),
        ]);

        DB::table('email_verification_codes')->where('email', $validated['email'])->delete();
        session()->forget('verify_email_address');

        $tokens = $this->jwtService->generateTokens($user);
        $accessCookie = $this->jwtService->getAccessTokenCookie($tokens['access_token']);
        $refreshCookie = $this->jwtService->getRefreshTokenCookie($tokens['refresh_token']);
        Auth::setUser($user);
        Auth::guard('web')->login($user, true);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Email verified successfully! Welcome to e-Blog.',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                ],
            ], 200)
                ->withCookie($accessCookie)
                ->withCookie($refreshCookie);
        }

        return redirect()->route('home')
            ->with('message', 'Email verified successfully! Welcome to e-Blog.')
            ->withCookie($accessCookie)
            ->withCookie($refreshCookie);
    }

    /**
     * Resend 6-digit email verification code.
     */
    public function resendVerificationCode(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return redirect()->back()->withErrors([
                'invalid' => 'User account not found.',
            ]);
        }

        if (! empty($user->email_verified_at)) {
            return redirect()->route('login')->with('message', 'Your email is already verified. Please log in.');
        }

        $dailyKey = 'verify-resend-daily:'.strtolower($validated['email']);

        // Daily limit: 3 times per day
        if (RateLimiter::tooManyAttempts($dailyKey, 3)) {
            $limitMsg = 'You have reached the maximum limit of 3 verification code requests per day. Please try again tomorrow.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $limitMsg], 429);
            }
            return redirect()->back()->withErrors(['invalid' => $limitMsg]);
        }

        $lastRecord = DB::table('email_verification_codes')->where('email', $validated['email'])->first();

        // Rate limit: 60 seconds cooldown
        if ($lastRecord && Carbon::parse($lastRecord->created_at)->addSeconds(60)->isFuture()) {
            $secondsLeft = 60 - Carbon::parse($lastRecord->created_at)->diffInSeconds(now());
            return redirect()->back()->withErrors([
                'invalid' => "Please wait {$secondsLeft} seconds before requesting another code.",
            ]);
        }

        $code = sprintf('%06d', random_int(100000, 999999));

        DB::table('email_verification_codes')->updateOrInsert(
            ['email' => $user->email],
            [
                'code' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        RateLimiter::hit($dailyKey, 86400);

        $this->resendMailService->sendVerificationCodeEmail($user, $code);

        $successMsg = 'A new 6-digit verification code has been sent to your email.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $successMsg]);
        }

        return redirect()->back()->with('message', $successMsg);
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
            $dailyKey = 'password-reset-daily:'.strtolower($validated['email']);

            // Daily limit: 3 times per day
            if (RateLimiter::tooManyAttempts($dailyKey, 3)) {
                $limitMsg = 'You have reached the maximum limit of 3 password reset requests per day. Please try again tomorrow.';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $limitMsg], 429);
                }
                return redirect()->back()->withErrors(['invalid' => $limitMsg]);
            }

            $lastRecord = DB::table('password_reset_tokens')->where('email', $user->email)->first();

            // Rate limit cooldown: 60 seconds between reset link requests
            if ($lastRecord && Carbon::parse($lastRecord->created_at)->addSeconds(60)->isFuture()) {
                $secondsLeft = 60 - Carbon::parse($lastRecord->created_at)->diffInSeconds(now());
                $cooldownMsg = "Please wait {$secondsLeft} seconds before requesting another reset link.";

                if ($request->wantsJson()) {
                    return response()->json(['message' => $cooldownMsg], 429);
                }

                return redirect()->back()->withErrors(['invalid' => $cooldownMsg]);
            }

            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => hash('sha256', $plainToken),
                    'created_at' => now(),
                ]
            );

            RateLimiter::hit($dailyKey, 86400);

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

        $record = DB::table('password_reset_tokens')
            ->where('token', hash('sha256', $token))
            ->first();

        if (! $record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            if ($record) {
                DB::table('password_reset_tokens')->where('email', $record->email)->delete();
            }

            return redirect()->route('password.request')->withErrors([
                'invalid' => 'This password reset link is invalid or has expired. Please request a new one.',
            ]);
        }

        return Inertia::render('ResetPassword', [
            'token' => $token,
            'email' => $record->email,
        ]);
    }

    /**
     * Handle Password Reset submission.
     */
    public function resetPassword(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|same:password_confirm',
            'password_confirm' => 'required|min:8',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('token', hash('sha256', $validated['token']))
            ->first();

        if (! $record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            if ($record) {
                DB::table('password_reset_tokens')->where('email', $record->email)->delete();
            }

            return redirect()->back()->withErrors([
                'invalid' => 'This password reset link is invalid or has expired. Please request a new one.',
            ])->withInput();
        }

        $user = User::where('email', $record->email)->first();

        if (! $user) {
            DB::table('password_reset_tokens')->where('email', $record->email)->delete();

            return redirect()->back()->withErrors([
                'invalid' => 'User account not found.',
            ])->withInput();
        }

        // Update password and clean up reset token
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $record->email)->delete();

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
