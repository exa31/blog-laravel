<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a new comment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|min:2|max:2000',
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        try {
            $user = Auth::user();
            $comment = Comment::create([
                'user_id' => $user->id,
                'post_id' => $validated['post_id'],
                'content' => $validated['content'],
            ]);

            $comment->load(['user:id,name,avatar']);
            $comment->setRelation('replies', collect());

            return response()->json([
                'message' => 'Comment posted successfully',
                'comment' => $comment,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to post comment. Please try again.',
            ], 500);
        }
    }

    /**
     * Paginate/scroll comments for a post.
     */
    public function addOnScroll(Request $request, int $id): JsonResponse
    {
        $skip = (int) $request->query('skip', 0);
        $limit = (int) $request->query('limit', 5);

        $comments = Comment::where('post_id', $id)
            ->withCount('replies')
            ->with([
                'user:id,name,avatar',
                'replies' => fn ($q) => $q->orderBy('created_at', 'asc')->with('user:id,name,avatar'),
            ])
            ->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($limit)
            ->get();

        return response()->json([
            'comments' => $comments,
        ], 200);
    }
}
