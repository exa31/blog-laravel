<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\SavePost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request): Response
    {
        $query = $request->query('q', '');
        $userId = Auth::id();

        $postsQuery = Post::query()
            ->published()
            ->searchByQuery($query)
            ->with(['user:id,name,email,avatar'])
            ->withCount(['comments', 'likes']);

        $totalPosts = (clone $postsQuery)->count();

        $posts = $postsQuery
            ->newPost()
            ->take(12)
            ->get();

        $savedPostIds = $userId
            ? SavePost::where('user_id', $userId)->pluck('post_id')->toArray()
            : [];

        $savedPosts = array_map(fn ($id) => ['post_id' => $id], $savedPostIds);

        return Inertia::render('Home', [
            'posts' => $posts,
            'totalPosts' => $totalPosts,
            'query' => $query ?? '',
            'savedPosts' => $savedPosts,
            'savedPostIds' => $savedPostIds,
        ]);
    }

    /**
     * Fetch additional posts on scroll.
     */
    public function addOnScroll(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $skip = (int) $request->query('skip', 0);
        $limit = (int) $request->query('limit', 6);

        $posts = Post::query()
            ->published()
            ->searchByQuery($query)
            ->with(['user:id,name,email,avatar'])
            ->withCount(['comments', 'likes'])
            ->newPost()
            ->skip($skip)
            ->take($limit)
            ->get();

        return response()->json([
            'posts' => $posts,
        ], 200);
    }

    /**
     * Display a single post detail.
     */
    public function show(string $slug): Response
    {
        $userId = Auth::id();

        $post = Post::query()
            ->with(['user:id,name,email,avatar'])
            ->withCount(['comments', 'likes'])
            ->where('slug', $slug)
            ->firstOrFail();

        $topComments = Comment::where('post_id', $post->id)
            ->withCount('replies')
            ->with([
                'user:id,name,avatar',
                'replies' => fn ($q) => $q->orderBy('created_at', 'asc')->with('user:id,name,avatar'),
            ])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $post->setRelation('comments', $topComments);

        $isLiked = $userId
            ? Like::where('user_id', $userId)->where('post_id', $post->id)->exists()
            : false;

        $isSaved = $userId
            ? SavePost::where('user_id', $userId)->where('post_id', $post->id)->exists()
            : false;

        $totalComments = Comment::where('post_id', $post->id)->count();

        return Inertia::render('DetailPost', [
            'post' => $post,
            'totalComments' => $totalComments,
            'isLiked' => $isLiked,
            'isSaved' => $isSaved,
        ]);
    }
}
