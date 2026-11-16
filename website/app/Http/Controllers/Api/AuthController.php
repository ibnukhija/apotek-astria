<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function loginMobile(Request $request)
    {
        $request->validate([
            'username' => 'required', 
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        // Memastikan username ada, password cocok, rolenya "owner", dan akun masih aktif
        if (!$user || !Hash::check($request->password, $user->password) || $user->role !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Username/Password salah atau bukan Owner',
            ], 200);
        }

        if ($user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun owner sedang dinonaktifkan',
            ], 200);
        }

        $token = $user->createToken('mobile-owner')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'token' => $token,
            ],
        ]);
    }

    public function logoutMobile(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}