<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function loginMobile(Request $request)
    {
        $request->validate([
            'username' => 'required', 
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        // Memastikan username ada, password cocok, dan rolenya adalah "owner"
        if ($user && Hash::check($request->password, $user->password) && $user->role === 'owner') {
            return response()->json([
                'success' => true,
                'message' => 'Login Berhasil',
                'data' => [
                    'id'   => $user->id,
                    'nama' => $user->nama,
                ]
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Username/Password salah atau bukan Owner'
        ], 200);
    }
}