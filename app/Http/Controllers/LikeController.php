<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
    /**
     * Toggle like/unlike on a post.
     */
    public function like(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();
        $post = Post::findOrFail($id);

        $existLike = Like::where('user_id', $userId)->where('post_id', $id)->first();

        if ($existLike) {
            DB::transaction(function () use ($existLike, $post) {
                $existLike->delete();
                $post->decrement('like');
            });

            $post->refresh();

            return response()->json([
                'message' => 'Post unliked',
                'like' => max(0, (int) $post->like),
                'isLiked' => false,
            ], 200);
        }

        DB::transaction(function () use ($userId, $id, $post) {
            Like::create([
                'user_id' => $userId,
                'post_id' => $id,
            ]);
            $post->increment('like');
        });

        $post->refresh();

        return response()->json([
            'message' => 'Post liked',
            'like' => (int) $post->like,
            'isLiked' => true,
        ], 200);
    }
}
