<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\SavePost;
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
        $q = request()->query('q');
        $totalPosts = Post::where(DB::raw('LOWER(title)'), 'LIKE', '%' . strtolower($q) . '%')->with('user')->count();
        $posts = Post::where(DB::raw('LOWER(title)'), 'LIKE', '%' . strtolower($q) . '%')->with('user')->take(5)->get();
        $savedPosts = SavePost::where('user_id', Auth::id())->select(['post_id'])->get();

        return Inertia::render('Home', [
            'posts' => $posts,
            'user' => $user,
            'isLogin' => $isLogin,
            'totalPosts' => $totalPosts,
            'query' => $q ?? '',
            'savedPosts' => $savedPosts,
        ]);
    }

    public function addOnScroll()
    {
        $q = request()->query('q');
        $skip = request()->query('skip');
        $posts = Post::where(DB::raw('LOWER(title)'), 'LIKE', '%' . strtolower($q) . '%')->with('user')->take(5)
            ->skip($skip)
            ->get();

        return response()->json([
            'posts' => $posts,
        ], 200);

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
        $isSaved = SavePost::whereHas('post', function ($query) use ($slug) {
            $query->where('slug', $slug);
        })->where('user_id', Auth::id())->exists();
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
            'isSaved' => $isSaved,
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
