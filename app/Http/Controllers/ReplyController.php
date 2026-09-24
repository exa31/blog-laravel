<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReplyController extends Controller
{
    /**
     * Store a reply to a comment.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|min:2|max:1000',
            'comment_id' => 'required|integer|exists:comments,id',
        ]);

        try {
            $user = Auth::user();
            $reply = Reply::create([
                'user_id' => $user->id,
                'comment_id' => $validated['comment_id'],
                'body' => $validated['body'],
            ]);

            $reply->load('user:id,name,avatar');

            return response()->json([
                'message' => 'Reply posted successfully',
                'reply' => $reply,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to post reply. Please try again.',
            ], 500);
        }
    }
}
