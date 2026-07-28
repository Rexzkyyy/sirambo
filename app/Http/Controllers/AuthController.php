<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if(Auth::attempt($credentials)){
        $request->session()->regenerate();

        // Redirect sesuai role
        $user = Auth::user();
        if($user->role == 'provinsi'){
            return redirect()->route('pdrb.dashboard'); // dashboard provinsi
        } else {
            return redirect()->route('pdrb.dashboard'); // dashboard kabupaten/kota
        }
    }

    return back()->withErrors([
        'email' => 'Email atau password salah.',
    ]);
}


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
