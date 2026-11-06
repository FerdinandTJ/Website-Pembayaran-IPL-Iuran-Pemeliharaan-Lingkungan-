<?php

namespace App\Http\Controllers;

use App\Services\SessionRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()->load('housing')]);
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
        ]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->forceFill(['password' => $data['password']])->save();
            app(SessionRevoker::class)->revoke($request->user(), $request->session()->getId());
        });
        $request->session()->regenerate();

        return back()->with('success', 'Password berhasil diubah. Sesi perangkat lain telah diakhiri.');
    }
}
