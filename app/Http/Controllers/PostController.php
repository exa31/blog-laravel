<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $isLogin = Auth::check();

        $user = Auth::user();

        $post = Post::with('user')->get();


        return Inertia::render('Home', [
            'posts' => $post,
            'user' => $user,
            'isLogin' => $isLogin,
        ]);
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $isLogin = Auth::check();
        $user = Auth::user();

        $post = Post::with('user')
            ->firstWhere('slug', $slug);

        // Ambil komentar dengan count replies, sorting, dan limit 5
        $topComments = Comment::where('post_id', $post->id)
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
            ->get();
        // Set top comments ke dalam post
        $post->setRelation('comments', $topComments);
        $isLiked = Like::where('user_id', Auth::id())->get();
        $isLiked = $isLiked->contains('post_id', $post->id);
        $totalComments = Comment::where('post_id', $post->id)->count();

        return Inertia::render('DetailPost', [
            'post' => $post,
            'totalComments' => $totalComments,
            'isLogin' => $isLogin,
            'isLiked' => $isLiked,
            'user' => $user,
        ]);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function like(Request $request)
    {
        $existLike = Like::where('user_id', Auth::id())->where('post_id', $request->id)->first();
        if ($existLike) {
            DB::beginTransaction();
            try {
                $existLike->delete();
                $post = Post::find($request->id);
                $post->decrement('like');
                DB::commit();
                return response()->json([
                    'message' => 'Success to unlike post',
                    'like' => $post->like,
                    'isLiked' => false
                ], 200);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'message' => $th->getMessage()
                ], 500);
            }
        } else {
            DB::beginTransaction();
            try {
                Like::create([
                    'user_id' => Auth::id(),
                    'post_id' => $request->id
                ]);
                $post = Post::find($request->id);
                $post->increment('like');
                DB::commit();
                return response()->json([
                    'message' => 'Success to like post',
                    'like' => $post->like,
                    'isLiked' => true
                ], 200);
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to like post t' . $th->getMessage()
                ], 500);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
