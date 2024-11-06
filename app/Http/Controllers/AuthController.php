<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login()
    {
        return Inertia::render('Login');
    }

    public function loginPost(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return redirect()->route('home');
        } else {
            return redirect()->back()->withErrors(['invalid' => 'Email or password is wrong'])->withInput();
        }

    }

    public function register()
    {
        return Inertia::render('Register');
    }

    public function registerPost(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|min:3|',
            'email' => 'required|email',
            'password' => 'required|min:8|same:password_confirm',
            'password_confirm' => 'required|min:8|same:password'
        ]);

        $user = User::firstWhere('email', $validated['email']);
        if ($user) {
            return redirect()->back()->withErrors(['sameEmail' => 'Email already exists'])->withInput();
        }

        $name = $validated['name'];
        $name = explode(' ', $name);
        $avatar = strtoupper($name[0][0] . $name[1][0]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'avatar' => $avatar,
                'password' => $validated['password']
            ]);
            Auth::login($user, true);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Something went wrong'])->withInput();
        }

        return redirect()->route('home');
    }

    public function logout()
    {
        Auth::logout();
        return;
    }
}
