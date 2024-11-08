<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReplyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index'])->name('home');

Route::get('/post/{slug}', [PostController::class, 'show'])->name('showPost');

Route::post('/like/{id}', [PostController::class, 'like'])->name('likePost')->middleware('auth');

Route::get('/login', [AuthController::class, 'login'])->name('login');

Route::post('/login', [AuthController::class, 'loginPost'])->name('loginPost');

Route::get('/register', [AuthController::class, 'register'])->name('register');

Route::post('/register', [AuthController::class, 'registerPost'])->name('registerPost');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/auth/google', [GoogleAuthController::class, 'google'])->name('auth.google');

Route::get('/auth/google-callback', [GoogleAuthController::class, 'googleRedirect'])->name('auth.google');

Route::post('/comments', [CommentController::class, 'store'])->name('commentsPost')->middleware('auth');

Route::get('/comments/{id}/on-scroll', [CommentController::class, 'addOnScroll'])->name('addCommentsOnScroll');

Route::post('/comments/replies', [ReplyController::class, 'store'])->name('repliesPost')->middleware('auth');