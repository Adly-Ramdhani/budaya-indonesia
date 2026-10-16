<?php

namespace App\Http\Controllers;

use App\Helpers\ApiFormatter; // sesuaikan namespace punyamu
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiFormatter::sendResponse(201, 'Registrasi berhasil', [
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $user  = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiFormatter::sendResponse(200, 'Login berhasil', [
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return ApiFormatter::sendResponse(200, 'Data pengguna', $request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiFormatter::sendResponse(200, 'Logout berhasil', null);
    }
}