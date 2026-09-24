<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(protected JwtService $jwtService) {}

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
}
