<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    function dashboard() {
        return view('admin.register');
    }

    // Handle the registration
    function register(Request $request) {
        // Validate the form inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'housing_address' => 'nullable|string|max:255',
            'housing_name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:15',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pengurus',
            'housing_address' => $request->housing_address,
            'housing_name' => $request->housing_name,
            'phone_number' => $request->phone_number,
        ]);
    }
}
