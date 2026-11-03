<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:128'],
        ]);

        $field = filter_var($credentials['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([
            $field => $credentials['identifier'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            return back()->withErrors([
                'identifier' => 'Email/username atau password tidak sesuai.',
            ])->onlyInput('identifier');
        }

        if ($request->user()->housing_id && ! $request->user()->housing?->is_active) {
            Auth::logout();

            return back()->withErrors([
                'identifier' => 'Perumahan sedang dinonaktifkan. Hubungi administrator.',
            ])->onlyInput('identifier');
        }

        $request->session()->regenerate();

        return redirect()->intended(route($this->dashboardRoute($request->user()->role)));
    }

    public function destroy(Request $request): RedirectResponse
    {
        PushSubscription::where('user_id', $request->user()->id)->where('session_hash', hash('sha256', $request->session()->getId()))->delete();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function home(Request $request): RedirectResponse
    {
        return redirect()->route($this->dashboardRoute($request->user()->role));
    }

    private function dashboardRoute(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'pengurus' => 'pengurus.dashboard',
            'warga' => 'warga.dashboard',
            default => 'login',
        };
    }
}
