<?php

namespace App\Http\Controllers;

use App\Models\SavePost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavePostController extends Controller
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
    public function store(Request $request, SavePost $savePost)
    {
        DB::beginTransaction();
        try {
            $savePost::create([
                'user_id' => Auth::id(),
                'post_id' => $request->post_id,
            ]);
            DB::commit();
            return response()->json(['message' => 'Post saved successfully'], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong: ' . $th->getMessage()], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SavePost $savePost)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, SavePost $savePost)
    {
        DB::beginTransaction();
        try {
            $savePost::where('user_id', Auth::id())->where('post_id', $id)->delete();
            DB::commit();
            return response()->json(['message' => 'Post delete successfully'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
}