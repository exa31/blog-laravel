<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function google()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleRedirect()
    {
        $userLogin = Socialite::driver('google')->user();

        $user = User::firstWhere('email', $userLogin->email);

        if (!$user) {
            return redirect()->route('register');
        }
        Auth::login($user, true);

        return redirect()->route('home');
    }
}

