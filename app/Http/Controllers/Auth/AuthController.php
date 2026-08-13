<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ]);

        // Login dengan email ATAU nomor WhatsApp
        $login = $credentials['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$field => $login, 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'login' => 'Email/WhatsApp atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['login' => 'Akun Anda dinonaktifkan.']);
        }

        ActivityLogger::log('user_login', 'Login', $user->name.' berhasil login');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
