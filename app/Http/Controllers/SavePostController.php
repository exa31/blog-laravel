<?php

namespace App\Http\Controllers;

use App\Models\SavePost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SavePostController extends Controller
{
    /**
     * Display a listing of saved posts.
     */
    public function index(): Response
    {
        $userId = Auth::id();

        $savedPostsQuery = SavePost::where('user_id', $userId)
            ->with(['post.user:id,name,email,avatar'])
            ->orderBy('created_at', 'desc');

        $totalPosts = $savedPostsQuery->count();
        $savedPosts = $savedPostsQuery->take(12)->get();

        return Inertia::render('SavePost', [
            'savedPosts' => $savedPosts,
            'posts' => $savedPosts,
            'totalPosts' => $totalPosts,
        ]);
    }

    /**
     * Load more saved posts on scroll.
     */
    public function addOnScroll(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $skip = (int) $request->query('skip', 0);
        $limit = (int) $request->query('limit', 6);

        $savedPosts = SavePost::where('user_id', $userId)
            ->with(['post.user:id,name,email,avatar'])
            ->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($limit)
            ->get();

        return response()->json([
            'posts' => $savedPosts,
        ], 200);
    }

    /**
     * Save a post to bookmarks.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        $userId = Auth::id();

        $existing = SavePost::where('user_id', $userId)
            ->where('post_id', $validated['post_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Post already bookmarked',
            ], 200);
        }

        SavePost::create([
            'user_id' => $userId,
            'post_id' => $validated['post_id'],
        ]);

        return response()->json([
            'message' => 'Post bookmarked successfully',
        ], 201);
    }

    /**
     * Remove a post from bookmarks.
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = Auth::id();

        SavePost::where('user_id', $userId)
            ->where('post_id', $id)
            ->delete();

        return response()->json([
            'message' => 'Post removed from bookmarks',
        ], 200);
    }
}
