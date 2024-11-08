<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|min:3',
            'post_id' => 'required',
        ]);

        try {
            $comment = Comment::create([
                'user_id' => Auth::id(),
                'post_id' => $request->post_id,
                'content' => $request->content,
            ]);
            return response()->json(['message' => 'Comment created successfully', 'comment' => $comment], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to create comment ' . $th], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Comment $comment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comment $comment)
    {
        //
    }

    public function addOnScroll($id)
    {
        $comments = Comment::where('post_id', $id)
            ->withCount('replies')
            ->with([
                'replies' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'replies.user',
                'user',
            ])
            ->orderBy('replies_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->skip(request()->skip)
            ->get();
        return response()->json(['comments' => $comments], 200);
    }
}
