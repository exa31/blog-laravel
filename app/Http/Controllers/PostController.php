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

        $name = $user->name;
        $post = Post::with('user')->get();
        $name = explode(' ', $name);
        $avatar = strtoupper($name[0][0] . $name[1][0]);
        return Inertia::render('Home', [
            'posts' => $post,
            'user' => $user,
            'isLogin' => $isLogin,
            'avatar' => $avatar
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
        $post = Post::with('user')->firstWhere('slug', $slug);
        return Inertia::render('DetailPost', [
            'post' => $post
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

    public function like(string $id)
    {
        $existLike = Like::where('user_id', Auth::id())->where('post_id', $id)->first();

        if ($existLike) {
            DB::beginTransaction();
            try {
                $existLike->delete();
                Post::find($id)->decrement('like');
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to like post'
                ], 500);
            }
        } else {
            DB::beginTransaction();
            try {
                Like::create([
                    'user_id' => Auth::id(),
                    'post_id' => $id
                ]);
                Post::find($id)->increment('like');
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Failed to like post'
                ], 500);
            }
        }
        return response()->json([
            'message' => 'Post liked successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
