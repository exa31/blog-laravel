<?php

use App\Http\Controllers\AppleAuthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SavePostController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [PostController::class, 'index'])->name('home');
Route::get('/posts/on-scroll', [PostController::class, 'addOnScroll'])->name('posts.scroll');
Route::get('/post/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/comments/{id}/on-scroll', [CommentController::class, 'addOnScroll'])->name('comments.scroll');

// Guest & Auth routes
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginPost'])->name('login.post');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'registerPost'])->name('register.post');
Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('auth.refresh');

// Google OAuth
Route::get('/auth/google', [GoogleAuthController::class, 'google'])->name('auth.google');
Route::get('/auth/google-callback', [GoogleAuthController::class, 'googleRedirect'])->name('auth.google.callback');

// Apple OAuth & Server-to-Server Notifications
Route::get('/auth/apple', [AppleAuthController::class, 'apple'])->name('auth.apple');
Route::match(['get', 'post'], '/auth/apple-callback', [AppleAuthController::class, 'appleRedirect'])->name('auth.apple.callback');
Route::post('/auth/apple/webhook', [AppleAuthController::class, 'webhook'])->name('auth.apple.webhook');

// Forgot & Reset Password routes (Resend Email)
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordPage'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// OAuth Set Password Flow
Route::get('/auth/set-password', [AuthController::class, 'showSetPassword'])->name('auth.set-password');
Route::post('/auth/set-password', [AuthController::class, 'storeSetPassword'])->name('auth.set-password.store');
Route::post('/auth/set-password/cancel', [AuthController::class, 'cancelSetPassword'])->name('auth.set-password.cancel');

// Protected routes (JWT Authenticated)
Route::middleware('jwt.auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/save-posts', [SavePostController::class, 'index'])->name('posts.saved');
    Route::get('/save-posts/on-scroll', [SavePostController::class, 'addOnScroll'])->name('posts.saved.scroll');
    Route::post('/save-post', [SavePostController::class, 'store'])->name('posts.save');
    Route::delete('/save-post/{id}', [SavePostController::class, 'destroy'])->name('posts.unsave');
    Route::post('/like/{id}', [LikeController::class, 'like'])->name('posts.like');
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comments/replies', [ReplyController::class, 'store'])->name('replies.store');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');

    // OAuth Unbind / Disconnect Endpoints
    Route::post('/auth/apple/unbind', [AppleAuthController::class, 'unbind'])->name('auth.apple.unbind');
    Route::post('/auth/google/unbind', [GoogleAuthController::class, 'unbind'])->name('auth.google.unbind');
});
