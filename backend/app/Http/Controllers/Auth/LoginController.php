<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function loginView()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
