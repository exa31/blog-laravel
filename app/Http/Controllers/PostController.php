<?php

namespace App\Http\Controllers;

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
        if ($user) {
            $name = $user->name;
            $name = explode(' ', $name);
            $avatar = strtoupper($name[0][0] . $name[1][0]);
        }

        $post = Post::with('user')->get();


        return Inertia::render('Home', [
            'posts' => $post,
            'user' => $user,
            'isLogin' => $isLogin,
            'avatar' => $avatar ?? null
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
        if ($user) {
            $name = $user->name;
            $name = explode(' ', $name);
            $avatar = strtoupper($name[0][0] . $name[1][0]);
        }
        $post = Post::with('user')->firstWhere('slug', $slug);
        $liked = Like::where('user_id', Auth::id())->get();
        $liked = $liked->contains('post_id', $post->id);
        return Inertia::render('DetailPost', [
            'post' => $post,
            'isLogin' => $isLogin,
            'isLiked' => $liked,
            'user' => $user,
            'avatar' => $avatar ?? null,
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
