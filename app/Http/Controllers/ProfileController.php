<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's display name.
     */
    public function updateName(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $user->name = trim($validated['name']);
        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Display name updated successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'has_google_auth' => $user->has_google_auth,
                'has_apple_auth' => $user->has_apple_auth,
                'can_unbind_apple' => $user->can_unbind_apple,
                'auth_provider' => $user->auth_provider,
            ],
        ], 200);
    }

    /**
     * Update the authenticated user's avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $disk = config('filesystems.default', 'public');
        $newAvatarPath = null;

        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);

            $file = $request->file('avatar');
            $newAvatarPath = app(ImageOptimizerService::class)->optimizeAndStore(
                $file,
                directory: 'avatars',
                disk: $disk,
                quality: 85,
                maxWidth: 600
            );
        } elseif ($request->filled('avatar_base64')) {
            $base64Data = $request->input('avatar_base64');

            try {
                $newAvatarPath = app(ImageOptimizerService::class)->optimizeAndStore(
                    $base64Data,
                    directory: 'avatars',
                    disk: $disk,
                    quality: 85,
                    maxWidth: 600
                );
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Invalid image payload.'], 422);
            }
        } else {
            return response()->json(['message' => 'No avatar image provided.'], 422);
        }

        // Clean up old avatar from storage if present and not an external URL
        if ($user->avatar && ! str_starts_with($user->avatar, 'http://') && ! str_starts_with($user->avatar, 'https://')) {
            try {
                if (Storage::disk($disk)->exists($user->avatar)) {
                    Storage::disk($disk)->delete($user->avatar);
                }
            } catch (\Throwable $e) {
                // Ignore cleanup errors
            }
        }

        $user->avatar = $newAvatarPath;
        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Profile picture updated successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
            ],
            'avatar_url' => $user->avatar_url,
        ], 200);
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function deleteAvatar(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $disk = config('filesystems.default', 'public');

        if ($user->avatar && ! str_starts_with($user->avatar, 'http://') && ! str_starts_with($user->avatar, 'https://')) {
            try {
                if (Storage::disk($disk)->exists($user->avatar)) {
                    Storage::disk($disk)->delete($user->avatar);
                }
            } catch (\Throwable $e) {
                // Ignore deletion errors
            }
        }

        $user->avatar = null;
        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Profile picture removed successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => null,
                'avatar_url' => null,
            ],
            'avatar_url' => null,
        ], 200);
    }
}
