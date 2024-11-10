<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeController extends Controller
{
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
}
