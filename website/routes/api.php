<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::post('/login-mobile', function (Request $request) {
    $request->validate([
        'username' => 'required', 
        'password' => 'required'
    ]);
    
    $user = User::where('username', $request->username)->first();

    // Validasi kecocokan password dan pastikan role adalah "owner"
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
    ], 401);
});