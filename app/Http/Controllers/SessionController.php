<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    function index()
    {
        return view('login');
    }

    function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
            'password' => 'required',
        ]);

        $identifier = $request->input('identifier');
        $password = $request->input('password');

        // Determine whether the user is logging in using email or username
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$fieldType => $identifier, 'password' => $password])) {
            $request->session()->regenerate();

            if (Auth::user()->role == 'admin') {
                return redirect('/admin/dashboard');
            } elseif (Auth::user()->role == 'pengurus') {
                return redirect('/pengurus/dashboard');
            } elseif (Auth::user()->role == 'warga') {
                return redirect('/warga/dashboard');
            }
        }else {
            // Authentication failed
            return redirect('/')->withErrors(['login_error' => 'Login gagal. Silakan cek kembali identitas dan password Anda.']);
        }
    }

    function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    function profile()
    {
        $user = Auth::user();
        return view($user->role . '.profile', compact('user'));
    }

    function admin()
    {
        return view('admin.register');
    }

    function pengurus()
    {
        echo "This is the pengurus page";
        echo Auth::user()->name;
    }

    function warga()
    {
        echo "This is the warga page";
        echo Auth::user()->name;
    }
}
