<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SavePostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index'])->name('home');

Route::get('/posts/on-scroll', [PostController::class, 'addOnScroll'])->name('addOnScrollPosts');

Route::get('/post/{slug}', [PostController::class, 'show'])->name('showPost');

Route::get('/login', [AuthController::class, 'login'])->name('login');

Route::get('/comments/{id}/on-scroll', [CommentController::class, 'addOnScroll'])->name('addCommentsOnScroll');

Route::get('/register', [AuthController::class, 'register'])->name('register');

Route::get('/save-posts', [SavePostController::class, 'index'])->name('savePost')->middleware('auth');

Route::get('/save-posts/on-scroll', [SavePostController::class, 'addOnScroll'])->name('addOnScrollSavePosts')->middleware('auth');

Route::get('/auth/google', [GoogleAuthController::class, 'google'])->name('auth.google');

Route::get('/auth/google-callback', [GoogleAuthController::class, 'googleRedirect'])->name('auth.google');

Route::post('/login', [AuthController::class, 'loginPost'])->name('loginPost');

Route::post('/register', [AuthController::class, 'registerPost'])->name('registerPost');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::post('/comments', [CommentController::class, 'store'])->name('commentsPost')->middleware('auth');

Route::post('/comments/replies', [ReplyController::class, 'store'])->name('repliesPost')->middleware('auth');

Route::post('/save-post', [SavePostController::class, 'store'])->name('savePost')->middleware('auth');

Route::post('/like/{id}', [LikeController::class, 'like'])->name('likePost')->middleware('auth');

Route::delete('/save-post/{id}', [SavePostController::class, 'destroy'])->name('savePost')->middleware('auth');